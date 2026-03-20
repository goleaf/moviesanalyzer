<?php

use App\Actions\StartScanAction;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Cache::flush();
});

it('queues a scan and stores rescan mode in cache', function (): void {
    $result = app(StartScanAction::class)->handle(true);

    $progressKey = (string) config('cineclean.scan.progress_cache_key');
    $startFlagKey = (string) config('cineclean.scan.start_flag_cache_key');

    /** @var array<string, mixed>|null $progress */
    $progress = Cache::get($progressKey);

    expect($result['already_running'])->toBeFalse()
        ->and($result['payload']['status'])->toBe('queued')
        ->and($result['payload']['rescan_all'])->toBeTrue()
        ->and($progress)->not->toBeNull()
        ->and($progress['status'])->toBe('queued')
        ->and($progress['rescan_all'])->toBeTrue()
        ->and(Cache::get($startFlagKey))->toBeTrue();
});

it('returns already running when scan is active', function (): void {
    $progressKey = (string) config('cineclean.scan.progress_cache_key');
    $startFlagKey = (string) config('cineclean.scan.start_flag_cache_key');

    Cache::put($progressKey, ['running' => true], now()->addMinutes(5));

    $result = app(StartScanAction::class)->handle(false);

    expect($result['already_running'])->toBeTrue()
        ->and(Cache::get($startFlagKey))->toBeNull();
});

it('resets stale running progress and queues a new scan', function (): void {
    $progressKey = (string) config('cineclean.scan.progress_cache_key');
    $startFlagKey = (string) config('cineclean.scan.start_flag_cache_key');

    Cache::put($progressKey, [
        'running' => true,
        'finished' => false,
        'status' => 'matching',
        'heartbeat_at' => now()->subMinutes(30)->toIso8601String(),
    ], now()->addMinutes(5));

    $result = app(StartScanAction::class)->handle(false);

    /** @var array<string, mixed>|null $progress */
    $progress = Cache::get($progressKey);

    expect($result['already_running'])->toBeFalse()
        ->and($result['payload']['status'])->toBe('queued')
        ->and($result['payload']['rescan_all'])->toBeFalse()
        ->and($progress)->not->toBeNull()
        ->and($progress['status'])->toBe('queued')
        ->and($progress['rescan_all'])->toBeFalse()
        ->and(Cache::get($startFlagKey))->toBeTrue();
});
