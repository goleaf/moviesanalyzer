<?php

namespace App\Actions;

use App\Data\ParsedFilename;
use App\Data\TmdbMatch;
use App\Enums\MatchStatus;
use App\Models\MovieFile;
use App\Models\ScanLog;
use App\Services\FilenameParser;
use App\Services\SmbService;
use App\Services\TmdbService;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScanMovieLibraryAction
{
    public function __construct(
        public SmbService $smbService,
        public FilenameParser $filenameParser,
        public TmdbService $tmdbService,
    ) {}

    public function handle(?Closure $progressCallback = null, bool $rescanAll = false): ScanLog
    {
        $startedAt = now();
        $progressKey = (string) config('cineclean.scan.progress_cache_key');
        $cacheTtl = now()->addMinutes((int) config('cineclean.scan.cache_ttl_minutes', 360));

        $scanLog = ScanLog::query()->create([
            'started_at' => $startedAt,
            'status' => 'running',
            'notes' => null,
        ]);

        $videoFiles = $this->smbService->listVideoFiles();
        $total = count($videoFiles);

        $scannedToday = [];

        if (! $rescanAll) {
            $scannedToday = array_fill_keys(
                MovieFile::query()
                    ->select(['smb_path'])
                    ->whereDate('scanned_at', $startedAt->toDateString())
                    ->pluck('smb_path')
                    ->all(),
                true,
            );
        }

        $existing = MovieFile::query()
            ->select(['id', 'smb_path'])
            ->whereIn('smb_path', array_column($videoFiles, 'smb_path'))
            ->get()
            ->keyBy('smb_path');

        $matched = 0;
        $unmatched = 0;

        $emitProgress = function (array $payload) use ($progressKey, $cacheTtl, $progressCallback): void {
            Cache::put($progressKey, $payload, $cacheTtl);

            if ($progressCallback !== null) {
                $progressCallback($payload);
            }
        };

        $emitProgress([
            'running' => true,
            'finished' => false,
            'status' => 'listing',
            'rescan_all' => $rescanAll,
            'current' => 0,
            'total' => $total,
            'file' => null,
            'matched' => 0,
            'unmatched' => 0,
            'started_at' => $startedAt->toIso8601String(),
            'finished_at' => null,
            'eta_seconds' => null,
        ]);

        foreach ($videoFiles as $index => $videoFile) {
            $current = $index + 1;
            $now = now();
            $elapsedSeconds = max(1, $startedAt->diffInSeconds($now));
            $etaSeconds = $current > 0 ? (int) (($elapsedSeconds / $current) * max(0, $total - $current)) : null;
            $path = $videoFile['smb_path'];

            if (isset($scannedToday[$path])) {
                $emitProgress([
                    'running' => true,
                    'finished' => false,
                    'status' => 'skipped',
                    'rescan_all' => $rescanAll,
                    'current' => $current,
                    'total' => $total,
                    'file' => $videoFile['filename'],
                    'matched' => $matched,
                    'unmatched' => $unmatched,
                    'started_at' => $startedAt->toIso8601String(),
                    'finished_at' => null,
                    'eta_seconds' => $etaSeconds,
                ]);

                continue;
            }

            $match = TmdbMatch::unmatched();
            $fallbackBaseName = pathinfo($videoFile['filename'], PATHINFO_FILENAME);
            $parsedFilename = new ParsedFilename(
                originalFilename: $videoFile['filename'],
                baseName: $fallbackBaseName,
                cleanTitle: $fallbackBaseName,
                releaseYear: null,
                searchQueries: [$fallbackBaseName],
            );

            try {
                $parsedFilename = $this->filenameParser->parse($videoFile['filename']);
                $match = $this->tmdbService->match($parsedFilename);
            } catch (Throwable $exception) {
                Log::warning('Failed to match movie file', [
                    'file' => $videoFile['filename'],
                    'error' => $exception->getMessage(),
                ]);
            }

            if (in_array($match->status, [MatchStatus::Matched, MatchStatus::Uncertain], true)) {
                $matched++;
            } else {
                $unmatched++;
            }

            $attributes = [
                'smb_path' => $videoFile['smb_path'],
                'filename' => $videoFile['filename'],
                'file_size_bytes' => $videoFile['file_size_bytes'],
                'extension' => $videoFile['extension'],
                'parsed_base_name' => $parsedFilename->baseName,
                'parsed_clean_title' => $parsedFilename->cleanTitle,
                'parsed_search_queries' => $parsedFilename->searchQueries,
                'parsed_release_year' => $parsedFilename->releaseYear,
                'scanned_at' => $now,
                ...$match->toDatabaseAttributes(),
            ];

            $existingMovieFile = $existing->get($path);

            if ($existingMovieFile === null) {
                $created = MovieFile::query()->create($attributes);
                $existing->put($path, $created->only(['id', 'smb_path']));
            } else {
                MovieFile::query()
                    ->whereKey((int) $existingMovieFile['id'])
                    ->update($attributes);
            }

            $emitProgress([
                'running' => true,
                'finished' => false,
                'status' => 'matching',
                'rescan_all' => $rescanAll,
                'current' => $current,
                'total' => $total,
                'file' => $videoFile['filename'],
                'matched' => $matched,
                'unmatched' => $unmatched,
                'started_at' => $startedAt->toIso8601String(),
                'finished_at' => null,
                'eta_seconds' => $etaSeconds,
            ]);
        }

        $finishedAt = now();

        $scanLog->update([
            'finished_at' => $finishedAt,
            'total_files' => $total,
            'matched' => $matched,
            'unmatched' => $unmatched,
            'status' => 'completed',
            'notes' => null,
        ]);

        $emitProgress([
            'running' => false,
            'finished' => true,
            'status' => 'complete',
            'rescan_all' => $rescanAll,
            'current' => $total,
            'total' => $total,
            'file' => null,
            'matched' => $matched,
            'unmatched' => $unmatched,
            'started_at' => $startedAt->toIso8601String(),
            'finished_at' => $finishedAt->toIso8601String(),
            'eta_seconds' => 0,
        ]);

        return $scanLog->fresh();
    }
}
