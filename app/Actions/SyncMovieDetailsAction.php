<?php

namespace App\Actions;

use App\Models\MovieFile;
use App\Services\TmdbImageStorageService;
use App\Services\TmdbService;
use Illuminate\Support\Arr;

class SyncMovieDetailsAction
{
    public function __construct(
        private TmdbService $tmdbService,
        private TmdbImageStorageService $tmdbImageStorageService,
    ) {}

    /**
     * @return array{
     *     synced: bool,
     *     tmdb_id: int|null,
     *     updated_records: int,
     *     message: string
     * }
     */
    public function handle(MovieFile $movieFile): array
    {
        if ($movieFile->tmdb_id === null) {
            return [
                'synced' => false,
                'tmdb_id' => null,
                'updated_records' => 0,
                'message' => 'TMDB sync skipped: the movie has no TMDB id.',
            ];
        }

        $tmdbId = (int) $movieFile->tmdb_id;
        $fullPayload = $this->tmdbService->fetchMovieDetailsPayload($tmdbId);

        if (! is_array($fullPayload)) {
            return [
                'synced' => false,
                'tmdb_id' => $tmdbId,
                'updated_records' => 0,
                'message' => 'TMDB sync failed: details payload was not returned by TMDB.',
            ];
        }

        $normalizedMovie = $this->tmdbService->findMovieById($tmdbId);

        if (! is_array($normalizedMovie)) {
            return [
                'synced' => false,
                'tmdb_id' => $tmdbId,
                'updated_records' => 0,
                'message' => 'TMDB sync failed: normalized movie metadata was not available.',
            ];
        }

        $localImages = $this->tmdbImageStorageService->syncMovieImages($tmdbId, $fullPayload);
        $existingMetadata = is_array($movieFile->tmdb_metadata) ? $movieFile->tmdb_metadata : [];
        $normalizedMetadata = is_array($normalizedMovie['tmdb_metadata'] ?? null)
            ? $normalizedMovie['tmdb_metadata']
            : [];
        $mergedMetadata = array_replace_recursive(
            $existingMetadata,
            $normalizedMetadata,
            [
                'full_payload' => $fullPayload,
                'full_synced_at' => now()->toIso8601String(),
                'local_images' => $localImages,
                'local_images_synced_at' => now()->toIso8601String(),
            ],
        );

        $firstLocalPosterUrl = Arr::get($localImages, 'posters.0.local_url');
        $posterPath = is_string($firstLocalPosterUrl) && trim($firstLocalPosterUrl) !== ''
            ? $firstLocalPosterUrl
            : (isset($normalizedMovie['poster_path']) && is_string($normalizedMovie['poster_path'])
                ? $normalizedMovie['poster_path']
                : null);
        $releaseYear = isset($normalizedMovie['release_year']) && is_numeric($normalizedMovie['release_year'])
            ? (int) $normalizedMovie['release_year']
            : null;

        $updateAttributes = [
            'tmdb_title' => isset($normalizedMovie['title']) && is_string($normalizedMovie['title'])
                ? $normalizedMovie['title']
                : null,
            'tmdb_original_title' => isset($normalizedMovie['original_title']) && is_string($normalizedMovie['original_title'])
                ? $normalizedMovie['original_title']
                : null,
            'tmdb_original_language' => isset($normalizedMovie['tmdb_original_language']) && is_string($normalizedMovie['tmdb_original_language'])
                ? $normalizedMovie['tmdb_original_language']
                : null,
            'tmdb_year' => $releaseYear,
            'movie_year' => $releaseYear,
            'tmdb_runtime' => isset($normalizedMovie['tmdb_runtime']) && is_numeric($normalizedMovie['tmdb_runtime'])
                ? (int) $normalizedMovie['tmdb_runtime']
                : null,
            'tmdb_release_date' => isset($normalizedMovie['tmdb_release_date']) && is_string($normalizedMovie['tmdb_release_date'])
                ? $normalizedMovie['tmdb_release_date']
                : null,
            'tmdb_tagline' => isset($normalizedMovie['tmdb_tagline']) && is_string($normalizedMovie['tmdb_tagline'])
                ? $normalizedMovie['tmdb_tagline']
                : null,
            'tmdb_status' => isset($normalizedMovie['tmdb_status']) && is_string($normalizedMovie['tmdb_status'])
                ? $normalizedMovie['tmdb_status']
                : null,
            'tmdb_imdb_id' => isset($normalizedMovie['tmdb_imdb_id']) && is_string($normalizedMovie['tmdb_imdb_id'])
                ? $normalizedMovie['tmdb_imdb_id']
                : null,
            'tmdb_poster_path' => $posterPath,
            'tmdb_overview' => isset($normalizedMovie['overview']) && is_string($normalizedMovie['overview'])
                ? $normalizedMovie['overview']
                : null,
            'tmdb_vote_average' => isset($normalizedMovie['vote_average']) && is_numeric($normalizedMovie['vote_average'])
                ? (float) $normalizedMovie['vote_average']
                : null,
            'tmdb_popularity' => isset($normalizedMovie['tmdb_popularity']) && is_numeric($normalizedMovie['tmdb_popularity'])
                ? (float) $normalizedMovie['tmdb_popularity']
                : null,
            'tmdb_vote_count' => isset($normalizedMovie['tmdb_vote_count']) && is_numeric($normalizedMovie['tmdb_vote_count'])
                ? (int) $normalizedMovie['tmdb_vote_count']
                : null,
            'tmdb_url' => isset($normalizedMovie['tmdb_url']) && is_string($normalizedMovie['tmdb_url'])
                ? $normalizedMovie['tmdb_url']
                : null,
            'tmdb_metadata' => $mergedMetadata,
            'scanned_at' => now(),
        ];

        $updatedRecords = MovieFile::query()
            ->where('tmdb_id', $tmdbId)
            ->update($updateAttributes);

        return [
            'synced' => true,
            'tmdb_id' => $tmdbId,
            'updated_records' => $updatedRecords,
            'message' => sprintf('TMDB full sync complete. Updated %d local file record(s).', $updatedRecords),
        ];
    }
}
