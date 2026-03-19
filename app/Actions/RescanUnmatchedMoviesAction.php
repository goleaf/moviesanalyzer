<?php

namespace App\Actions;

use App\Data\ParsedFilename;
use App\Data\TmdbMatch;
use App\Enums\MatchStatus;
use App\Models\MovieFile;
use App\Models\ScanLog;
use App\Services\FilenameParser;
use App\Services\TmdbService;
use Illuminate\Support\Facades\Log;
use Throwable;

class RescanUnmatchedMoviesAction
{
    public function __construct(
        private FilenameParser $filenameParser,
        private TmdbService $tmdbService,
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
                $fallbackBaseName = pathinfo($movieFile->filename, PATHINFO_FILENAME);
                $parsedFilename = new ParsedFilename(
                    originalFilename: $movieFile->filename,
                    baseName: $fallbackBaseName,
                    cleanTitle: $fallbackBaseName,
                    releaseYear: null,
                    searchQueries: [$fallbackBaseName],
                );
                $match = TmdbMatch::unmatched();

                try {
                    $parsedFilename = $this->filenameParser->parse($movieFile->filename);
                    $match = $this->tmdbService->match($parsedFilename);
                } catch (Throwable $exception) {
                    $failed++;

                    Log::warning('Failed to rescan unmatched movie file', [
                        'movie_file_id' => $movieFile->id,
                        'file' => $movieFile->filename,
                        'error' => $exception->getMessage(),
                    ]);
                }

                if ($match->status === MatchStatus::Matched) {
                    $matched++;
                } elseif ($match->status === MatchStatus::Uncertain) {
                    $uncertain++;
                } else {
                    $unmatched++;
                }

                MovieFile::query()
                    ->whereKey($movieFile->id)
                    ->update([
                        'parsed_base_name' => $parsedFilename->baseName,
                        'parsed_clean_title' => $parsedFilename->cleanTitle,
                        'parsed_search_queries' => $parsedFilename->searchQueries,
                        'parsed_release_year' => $parsedFilename->releaseYear,
                        'scanned_at' => now(),
                        ...$match->toDatabaseAttributes(),
                    ]);
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
