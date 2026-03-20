<?php

use App\Models\MovieFile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders duplicate groups in livewire conflict center without confirm prompt on delete actions', function (): void {
    config()->set('cineclean.files.allow_delete', true);

    MovieFile::query()->create([
        'smb_path' => 'Movies/The.Matrix.1999.1080p.mkv',
        'filename' => 'The.Matrix.1999.1080p.mkv',
        'file_size_bytes' => 2_000_000_000,
        'extension' => 'mkv',
        'tmdb_id' => 603,
        'tmdb_title' => 'The Matrix',
        'tmdb_year' => 1999,
        'movie_year' => 1999,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    MovieFile::query()->create([
        'smb_path' => 'Movies/The.Matrix.1999.2160p.mkv',
        'filename' => 'The.Matrix.1999.2160p.mkv',
        'file_size_bytes' => 4_000_000_000,
        'extension' => 'mkv',
        'tmdb_id' => 603,
        'tmdb_title' => 'The Matrix',
        'tmdb_year' => 1999,
        'movie_year' => 1999,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $this->get(route('cineclean.duplicates.index'))
        ->assertSuccessful()
        ->assertSee('data-duplicate-group="603"', false)
        ->assertDontSee('wire:confirm.prompt', false)
        ->assertDontSee('Delete this file from SMB?', false);
});
