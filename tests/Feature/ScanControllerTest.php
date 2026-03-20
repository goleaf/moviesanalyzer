<?php

use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::flush();
});

it('queues a new scan when stale running progress exists', function (): void {
    $progressKey = (string) config('cineclean.scan.progress_cache_key');
    $startFlagKey = (string) config('cineclean.scan.start_flag_cache_key');

    Cache::put($progressKey, [
        'running' => true,
        'finished' => false,
        'status' => 'matching',
        'heartbeat_at' => now()->subMinutes(30)->toIso8601String(),
    ], now()->addMinutes(5));

    $this->post(route('cineclean.scan.start'), [
        'rescan_all' => 1,
    ])
        ->assertRedirect(route('cineclean.dashboard'))
        ->assertSessionHas('scan_requested', true);

    /** @var array<string, mixed>|null $progress */
    $progress = Cache::get($progressKey);

    expect(Cache::get($startFlagKey))->toBeTrue()
        ->and($progress)->not->toBeNull()
        ->and($progress['status'])->toBe('queued')
        ->and($progress['running'])->toBeFalse();
});

it('keeps blocking scan start when running heartbeat is recent', function (): void {
    $progressKey = (string) config('cineclean.scan.progress_cache_key');
    $startFlagKey = (string) config('cineclean.scan.start_flag_cache_key');

    Cache::put($progressKey, [
        'running' => true,
        'finished' => false,
        'status' => 'matching',
        'heartbeat_at' => now()->subSeconds(20)->toIso8601String(),
    ], now()->addMinutes(5));

    $this->post(route('cineclean.scan.start'), [
        'rescan_all' => 1,
    ])
        ->assertRedirect(route('cineclean.dashboard'))
        ->assertSessionHas('status', 'A scan is already running.');

    expect(Cache::get($startFlagKey))->toBeNull();
});
