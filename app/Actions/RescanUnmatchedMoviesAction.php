<?php

namespace App\Actions;

use App\Enums\MatchStatus;
use App\Models\MovieFile;
use App\Models\ScanLog;

class RescanUnmatchedMoviesAction
{
    public function __construct(
        private RescanMovieFileAction $rescanMovieFileAction,
    ) {}

    /**
     * @return array{processed: int, matched: int, uncertain: int, unmatched: int, failed: int}
     */
    public function handle(): array
    {
        $startedAt = now();
        $processed = 0;
        $matched = 0;
        $uncertain = 0;
        $unmatched = 0;
        $failed = 0;

        MovieFile::query()
            ->select([
                'id',
                'filename',
                'match_status',
            ])
            ->unmatched()
            ->orderBy('id')
            ->lazyById(200)
            ->each(function (MovieFile $movieFile) use (
                &$processed,
                &$matched,
                &$uncertain,
                &$unmatched,
                &$failed,
            ): void {
                $processed++;
                $result = $this->rescanMovieFileAction->handle($movieFile);

                if ($result['status'] === MatchStatus::Matched->value) {
                    $matched++;
                } elseif ($result['status'] === MatchStatus::Uncertain->value) {
                    $uncertain++;
                } elseif ($result['failed']) {
                    $failed++;
                } else {
                    $unmatched++;
                }
            });

        ScanLog::query()->create([
            'started_at' => $startedAt,
            'finished_at' => now(),
            'total_files' => $processed,
            'matched' => $matched + $uncertain,
            'unmatched' => $unmatched,
            'status' => 'completed',
            'notes' => sprintf(
                'rescan_unmatched: matched=%d uncertain=%d unmatched=%d failed=%d',
                $matched,
                $uncertain,
                $unmatched,
                $failed,
            ),
        ]);

        return [
            'processed' => $processed,
            'matched' => $matched,
            'uncertain' => $uncertain,
            'unmatched' => $unmatched,
            'failed' => $failed,
        ];
    }
}
