<?php

namespace App\Actions;

use Illuminate\Support\Facades\Cache;

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

        /** @var array<string, mixed> $progress */
        $progress = Cache::get($progressKey, []);

        if (($progress['running'] ?? false) === true) {
            return [
                'already_running' => true,
                'payload' => $progress,
            ];
        }

        Cache::put($startFlagKey, true, $cacheTtl);

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
        ];

        Cache::put($progressKey, $payload, $cacheTtl);

        return [
            'already_running' => false,
            'payload' => $payload,
        ];
    }
}
