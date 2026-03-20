<?php

namespace App\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Throwable;

class StartScanAction
{
    /**
     * @return array{already_running: bool, payload: array<string, mixed>}
     */
    public function handle(bool $rescanAll): array
    {
        $progressKey = (string) config('cineclean.scan.progress_cache_key');
        $startFlagKey = (string) config('cineclean.scan.start_flag_cache_key');
        $cacheTtl = now()->addMinutes((int) config('cineclean.scan.cache_ttl_minutes', 360));
        $staleAfterSeconds = (int) config('cineclean.scan.stale_after_seconds', 900);

        /** @var array<string, mixed> $progress */
        $progress = Cache::get($progressKey, []);

        if (($progress['running'] ?? false) === true && ! $this->isStaleProgress($progress, $staleAfterSeconds)) {
            return [
                'already_running' => true,
                'payload' => $progress,
            ];
        }

        if (($progress['running'] ?? false) === true) {
            Cache::put($progressKey, [
                ...$progress,
                'running' => false,
                'finished' => true,
                'status' => 'failed',
                'error' => 'Previous scan was interrupted and has been reset.',
                'finished_at' => now()->toIso8601String(),
                'heartbeat_at' => now()->toIso8601String(),
            ], $cacheTtl);
        }

        Cache::put($startFlagKey, true, $cacheTtl);
        $queuedAt = now()->toIso8601String();

        $payload = [
            'running' => false,
            'finished' => false,
            'status' => 'queued',
            'rescan_all' => $rescanAll,
            'current' => 0,
            'total' => 0,
            'file' => null,
            'eta_seconds' => null,
            'matched' => 0,
            'unmatched' => 0,
            'started_at' => null,
            'finished_at' => null,
            'requested_at' => $queuedAt,
            'heartbeat_at' => $queuedAt,
        ];

        Cache::put($progressKey, $payload, $cacheTtl);

        return [
            'already_running' => false,
            'payload' => $payload,
        ];
    }

    /**
     * @param  array<string, mixed>  $progress
     */
    private function isStaleProgress(array $progress, int $staleAfterSeconds): bool
    {
        if ($staleAfterSeconds <= 0) {
            return false;
        }

        $timestamp = $progress['heartbeat_at'] ?? $progress['started_at'] ?? $progress['requested_at'] ?? null;

        if (! is_string($timestamp) || $timestamp === '') {
            return false;
        }

        try {
            $heartbeat = CarbonImmutable::parse($timestamp);
        } catch (Throwable) {
            return false;
        }

        return $heartbeat->diffInSeconds(now()) > $staleAfterSeconds;
    }
}
