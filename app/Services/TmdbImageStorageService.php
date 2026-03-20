<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TmdbImageStorageService
{
    public function __construct(private TmdbService $tmdbService) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     posters: array<int, array<string, mixed>>,
     *     backdrops: array<int, array<string, mixed>>,
     *     logos: array<int, array<string, mixed>>
     * }
     */
    public function syncMovieImages(int $tmdbId, array $payload): array
    {
        $groups = $this->collectImageGroups($payload);
        $diskName = (string) config('cineclean.tmdb.image_disk', 'public');
        $disk = Storage::disk($diskName);
        $baseDirectory = trim((string) config('cineclean.tmdb.image_directory', 'tmdb/movies'), '/');
        $imageSize = trim((string) config('cineclean.tmdb.image_size', 'original'), '/');
        $timeoutSeconds = max(5, (int) config('cineclean.tmdb.image_timeout_seconds', 30));
        $storageRoot = trim(sprintf('%s/%d', $baseDirectory, $tmdbId), '/');

        $syncedImages = [
            'posters' => [],
            'backdrops' => [],
            'logos' => [],
        ];

        foreach ($groups as $group => $images) {
            foreach ($images as $image) {
                $tmdbFilePath = trim((string) ($image['file_path'] ?? ''));

                if ($tmdbFilePath === '') {
                    continue;
                }

                $remoteUrl = $this->tmdbService->buildImageUrl($tmdbFilePath, $imageSize);

                if ($remoteUrl === '') {
                    continue;
                }

                try {
                    $response = Http::timeout($timeoutSeconds)
                        ->connectTimeout(min($timeoutSeconds, 10))
                        ->get($remoteUrl);

                    if (! $response->successful()) {
                        continue;
                    }

                    $filename = $this->safeFilename($tmdbFilePath);
                    $localPath = trim(sprintf('%s/%s/%s', $storageRoot, $group, $filename), '/');
                    $disk->put($localPath, $response->body());

                    $syncedImages[$group][] = array_filter([
                        'tmdb_file_path' => $tmdbFilePath,
                        'local_path' => $localPath,
                        'local_url' => $disk->url($localPath),
                        'width' => isset($image['width']) && is_numeric($image['width']) ? (int) $image['width'] : null,
                        'height' => isset($image['height']) && is_numeric($image['height']) ? (int) $image['height'] : null,
                        'aspect_ratio' => isset($image['aspect_ratio']) && is_numeric($image['aspect_ratio'])
                            ? (float) $image['aspect_ratio']
                            : null,
                        'iso_639_1' => isset($image['iso_639_1']) && is_string($image['iso_639_1'])
                            ? $image['iso_639_1']
                            : null,
                        'vote_average' => isset($image['vote_average']) && is_numeric($image['vote_average'])
                            ? (float) $image['vote_average']
                            : null,
                        'vote_count' => isset($image['vote_count']) && is_numeric($image['vote_count'])
                            ? (int) $image['vote_count']
                            : null,
                    ], static fn (mixed $value): bool => $value !== null && $value !== '');
                } catch (Throwable) {
                    continue;
                }
            }
        }

        return [
            'posters' => array_values($syncedImages['posters']),
            'backdrops' => array_values($syncedImages['backdrops']),
            'logos' => array_values($syncedImages['logos']),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     posters: array<int, array<string, mixed>>,
     *     backdrops: array<int, array<string, mixed>>,
     *     logos: array<int, array<string, mixed>>
     * }
     */
    private function collectImageGroups(array $payload): array
    {
        $imagesPayload = is_array($payload['images'] ?? null) ? $payload['images'] : [];

        $posters = $this->normalizeImageRows($imagesPayload['posters'] ?? null);
        $backdrops = $this->normalizeImageRows($imagesPayload['backdrops'] ?? null);
        $logos = $this->normalizeImageRows($imagesPayload['logos'] ?? null);

        if (isset($payload['poster_path']) && is_string($payload['poster_path']) && trim($payload['poster_path']) !== '') {
            $posters[] = ['file_path' => trim($payload['poster_path'])];
        }

        if (isset($payload['backdrop_path']) && is_string($payload['backdrop_path']) && trim($payload['backdrop_path']) !== '') {
            $backdrops[] = ['file_path' => trim($payload['backdrop_path'])];
        }

        return [
            'posters' => $this->deduplicateByFilePath($posters),
            'backdrops' => $this->deduplicateByFilePath($backdrops),
            'logos' => $this->deduplicateByFilePath($logos),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeImageRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $filePath = $row['file_path'] ?? null;

            if (! is_string($filePath) || trim($filePath) === '') {
                continue;
            }

            $normalized[] = [
                'file_path' => trim($filePath),
                'width' => $row['width'] ?? null,
                'height' => $row['height'] ?? null,
                'aspect_ratio' => $row['aspect_ratio'] ?? null,
                'iso_639_1' => $row['iso_639_1'] ?? null,
                'vote_average' => $row['vote_average'] ?? null,
                'vote_count' => $row['vote_count'] ?? null,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function deduplicateByFilePath(array $rows): array
    {
        $deduplicated = [];

        foreach ($rows as $row) {
            $filePath = trim((string) ($row['file_path'] ?? ''));

            if ($filePath === '') {
                continue;
            }

            if (! array_key_exists($filePath, $deduplicated)) {
                $deduplicated[$filePath] = $row;
            }
        }

        return array_values($deduplicated);
    }

    private function safeFilename(string $tmdbFilePath): string
    {
        $basename = trim(pathinfo($tmdbFilePath, PATHINFO_BASENAME));

        if ($basename === '') {
            return sha1($tmdbFilePath).'.jpg';
        }

        $sanitized = preg_replace('/[^A-Za-z0-9._-]/', '-', $basename);

        if ($sanitized === null || trim($sanitized) === '') {
            return sha1($tmdbFilePath).'.jpg';
        }

        return $sanitized;
    }
}
