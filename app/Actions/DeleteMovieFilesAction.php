<?php

namespace App\Actions;

use App\Models\MovieFile;
use App\Services\SmbService;
use Throwable;

class DeleteMovieFilesAction
{
    public function __construct(private SmbService $smbService) {}

    /**
     * @param  array<int, int|string>  $movieFileIds
     * @return array{
     *     requested_count: int,
     *     processed_count: int,
     *     deleted_count: int,
     *     failed_count: int,
     *     results: array<int, array{
     *         movie_file_id: int,
     *         filename: string,
     *         success: bool,
     *         message: string
     *     }>
     * }
     */
    public function handle(array $movieFileIds): array
    {
        $normalizedIds = collect($movieFileIds)
            ->filter(static fn (mixed $id): bool => is_numeric($id))
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($normalizedIds === []) {
            return [
                'requested_count' => 0,
                'processed_count' => 0,
                'deleted_count' => 0,
                'failed_count' => 0,
                'results' => [],
            ];
        }

        $movieFilesById = MovieFile::query()
            ->select(['id', 'smb_path', 'filename'])
            ->whereIn('id', $normalizedIds)
            ->get()
            ->keyBy('id');

        $results = [];
        $deletedCount = 0;
        $failedCount = 0;

        foreach ($normalizedIds as $movieFileId) {
            /** @var MovieFile|null $movieFile */
            $movieFile = $movieFilesById->get($movieFileId);

            if ($movieFile === null) {
                $failedCount++;
                $results[] = [
                    'movie_file_id' => $movieFileId,
                    'filename' => '',
                    'success' => false,
                    'message' => 'Movie file not found.',
                ];

                continue;
            }

            try {
                $this->smbService->deleteFile($movieFile->smb_path);
                $movieFile->delete();

                $deletedCount++;
                $results[] = [
                    'movie_file_id' => $movieFileId,
                    'filename' => (string) $movieFile->filename,
                    'success' => true,
                    'message' => '',
                ];
            } catch (Throwable $throwable) {
                $failedCount++;
                $results[] = [
                    'movie_file_id' => $movieFileId,
                    'filename' => (string) $movieFile->filename,
                    'success' => false,
                    'message' => $throwable->getMessage(),
                ];
            }
        }

        return [
            'requested_count' => count($normalizedIds),
            'processed_count' => count($results),
            'deleted_count' => $deletedCount,
            'failed_count' => $failedCount,
            'results' => $results,
        ];
    }
}
