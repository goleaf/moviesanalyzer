<?php

namespace App\Http\Controllers;

use App\Actions\ScanMovieLibraryAction;
use App\Http\Requests\StartScanRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ScanController extends Controller
{
    public function start(StartScanRequest $request): RedirectResponse|JsonResponse
    {
        $progressKey = (string) config('cineclean.scan.progress_cache_key');
        $startFlagKey = (string) config('cineclean.scan.start_flag_cache_key');
        $cacheTtl = now()->addMinutes((int) config('cineclean.scan.cache_ttl_minutes', 360));

        /** @var array<string, mixed> $progress */
        $progress = Cache::get($progressKey, []);

        if (($progress['running'] ?? false) === true) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Scan is already running.'], 409);
            }

            return redirect()
                ->route('cineclean.dashboard')
                ->with('status', 'A scan is already running.');
        }

        Cache::put($startFlagKey, true, $cacheTtl);

        Cache::put($progressKey, [
            'running' => false,
            'finished' => false,
            'status' => 'queued',
            'current' => 0,
            'total' => 0,
            'file' => null,
            'eta_seconds' => null,
            'matched' => 0,
            'unmatched' => 0,
            'started_at' => null,
            'finished_at' => null,
        ], $cacheTtl);

        if ($request->expectsJson()) {
            return response()->json(['queued' => true]);
        }

        return redirect()
            ->route('cineclean.dashboard')
            ->with('scan_requested', true);
    }

    public function progress(ScanMovieLibraryAction $scanMovieLibraryAction): StreamedResponse
    {
        $progressKey = (string) config('cineclean.scan.progress_cache_key');
        $startFlagKey = (string) config('cineclean.scan.start_flag_cache_key');

        return response()->stream(function () use ($scanMovieLibraryAction, $progressKey, $startFlagKey): void {
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
                'current' => 0,
                'total' => 0,
                'file' => null,
                'eta_seconds' => null,
                'matched' => 0,
                'unmatched' => 0,
            ]);

            $emit($progress);

            if (Cache::pull($startFlagKey, false) === true && ($progress['running'] ?? false) === false) {
                try {
                    $scanMovieLibraryAction->handle(function (array $payload) use ($emit): void {
                        $emit($payload);
                    });
                } catch (Throwable $throwable) {
                    $failedPayload = [
                        ...$progress,
                        'running' => false,
                        'finished' => true,
                        'status' => 'failed',
                        'error' => $throwable->getMessage(),
                    ];

                    Cache::put($progressKey, $failedPayload, now()->addMinutes(30));
                    $emit($failedPayload);
                }
            }

            $attempts = 0;

            while (! connection_aborted() && $attempts < 600) {
                /** @var array<string, mixed> $latest */
                $latest = Cache::get($progressKey, $progress);

                $emit($latest);

                if (($latest['finished'] ?? false) === true) {
                    break;
                }

                usleep(500000);
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
