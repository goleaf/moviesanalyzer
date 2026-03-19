<?php

use App\Models\MovieFile;
use App\Models\ScanLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the dashboard', function (): void {
    ScanLog::query()->create([
        'started_at' => now()->subMinute(),
        'finished_at' => now(),
        'total_files' => 10,
        'matched' => 8,
        'unmatched' => 2,
        'status' => 'completed',
    ]);

    $this->get(route('cineclean.dashboard'))
        ->assertSuccessful()
        ->assertSee('CINECLEAN')
        ->assertSee('SCAN LIBRARY');
});

it('returns dashboard stats as json', function (): void {
    MovieFile::query()->create([
        'smb_path' => 'Movies/The.Matrix.mkv',
        'filename' => 'The.Matrix.mkv',
        'file_size_bytes' => 1_000_000,
        'extension' => 'mkv',
        'tmdb_id' => 603,
        'tmdb_title' => 'The Matrix',
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $this->getJson(route('cineclean.stats'))
        ->assertSuccessful()
        ->assertJsonStructure([
            'total_files',
            'duplicate_groups',
            'space_to_reclaim_bytes',
            'unmatched',
        ]);
});
