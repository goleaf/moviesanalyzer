<?php

namespace App\Actions;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class StreamScanProgressAction
{
    public function __construct(private ScanMovieLibraryAction $scanMovieLibraryAction) {}

    public function handle(): StreamedResponse
    {
        $progressKey = (string) config('cineclean.scan.progress_cache_key');
        $startFlagKey = (string) config('cineclean.scan.start_flag_cache_key');
        $cacheTtl = now()->addMinutes((int) config('cineclean.scan.cache_ttl_minutes', 360));
        $maxPollAttempts = max(1, (int) config('cineclean.scan.stream_max_poll_attempts', 50));
        $pollIntervalMicros = max(100000, ((int) config('cineclean.scan.stream_poll_interval_ms', 500)) * 1000);

        return response()->stream(function () use ($progressKey, $startFlagKey, $cacheTtl, $maxPollAttempts, $pollIntervalMicros): void {
            @set_time_limit(0);
            @ignore_user_abort(true);

            $emit = function (array $payload): void {
                echo "event: progress\n";
                echo 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE)."\n\n";
                @ob_flush();
                flush();
            };

            /** @var array<string, mixed> $progress */
            $progress = Cache::get($progressKey, [
                'running' => false,
                'finished' => false,
                'status' => 'idle',
                'rescan_all' => true,
                'current' => 0,
                'total' => 0,
                'file' => null,
                'eta_seconds' => null,
                'matched' => 0,
                'unmatched' => 0,
                'heartbeat_at' => null,
            ]);

            $emit($progress);

            if (Cache::pull($startFlagKey, false) === true && ($progress['running'] ?? false) === false) {
                try {
                    $this->scanMovieLibraryAction->handle(
                        function (array $payload) use ($emit): void {
                            $emit($payload);
                        },
                        (bool) ($progress['rescan_all'] ?? true),
                    );
                } catch (Throwable $throwable) {
                    $failedPayload = [
                        ...$progress,
                        'running' => false,
                        'finished' => true,
                        'status' => 'failed',
                        'error' => $throwable->getMessage(),
                        'heartbeat_at' => now()->toIso8601String(),
                        'finished_at' => now()->toIso8601String(),
                    ];

                    Cache::put($progressKey, $failedPayload, $cacheTtl);
                    $emit($failedPayload);
                }
            }

            $attempts = 0;

            while (! connection_aborted() && $attempts < $maxPollAttempts) {
                /** @var array<string, mixed> $latest */
                $latest = Cache::get($progressKey, $progress);

                $emit($latest);

                if (($latest['finished'] ?? false) === true) {
                    break;
                }

                usleep($pollIntervalMicros);
                $attempts++;
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }
}
