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
        ->assertSee('MOVIESANALYZER')
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

it('applies parser rules when filtering movies page with filename-like search query', function (): void {
    MovieFile::query()->create([
        'smb_path' => 'Movies/Archive/legacy-copy.mkv',
        'filename' => 'legacy-copy.mkv',
        'file_size_bytes' => 1_000_000,
        'extension' => 'mkv',
        'parsed_clean_title' => 'Legacy Copy',
        'parsed_release_year' => null,
        'tmdb_id' => 603,
        'tmdb_title' => 'The Matrix',
        'tmdb_original_title' => 'The Matrix',
        'tmdb_year' => 1999,
        'movie_year' => 1999,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $this->get(route('cineclean.movies.index', [
        'q' => 'The.Matrix.1999.1080p.BluRay.x264.mkv',
    ]))
        ->assertSuccessful()
        ->assertSee('The Matrix');
});

it('shows effective parsed title and year on unmatched page for filenames with technical tags', function (): void {
    MovieFile::query()->create([
        'smb_path' => 'Movies/Uncharted.2022.1080p.FLEX.mp4',
        'filename' => 'Uncharted.2022.1080p.FLEX.mp4',
        'file_size_bytes' => 2_000_000_000,
        'extension' => 'mp4',
        'parsed_clean_title' => null,
        'parsed_release_year' => null,
        'match_status' => 'unmatched',
        'scanned_at' => now(),
    ]);

    $this->get(route('cineclean.unmatched.index'))
        ->assertSuccessful()
        ->assertSee('Parsed:')
        ->assertSee('Uncharted')
        ->assertSee('(2022)')
        ->assertSee('data-clean-title="Uncharted"', false)
        ->assertSee('data-release-year="2022"', false)
        ->assertSee('id="manual-year"', false);
});

it('uses current parser rules on unmatched page instead of stale saved parsed title', function (): void {
    MovieFile::query()->create([
        'smb_path' => 'Movies/Uncharted.2022.1080p.FLEX.mp4',
        'filename' => 'Uncharted.2022.1080p.FLEX.mp4',
        'file_size_bytes' => 2_000_000_000,
        'extension' => 'mp4',
        'parsed_clean_title' => 'Uncharted 1080P FLEX Old Value',
        'parsed_release_year' => 2021,
        'match_status' => 'unmatched',
        'scanned_at' => now(),
    ]);

    $this->get(route('cineclean.unmatched.index'))
        ->assertSuccessful()
        ->assertSee('data-clean-title="Uncharted"', false)
        ->assertDontSee('data-clean-title="Uncharted 1080P FLEX Old Value"', false)
        ->assertSee('data-release-year="2022"', false);
});

it('does not show google research controls on unmatched page', function (): void {
    MovieFile::query()->create([
        'smb_path' => 'Movies/The.Matrix.1999.mkv',
        'filename' => 'The.Matrix.1999.mkv',
        'file_size_bytes' => 2_000_000_000,
        'extension' => 'mkv',
        'parsed_clean_title' => 'The Matrix',
        'parsed_release_year' => 1999,
        'match_status' => 'unmatched',
        'scanned_at' => now(),
    ]);

    $this->get(route('cineclean.unmatched.index'))
        ->assertSuccessful()
        ->assertDontSee('Google MCP')
        ->assertDontSee('Google Research')
        ->assertDontSee('id="manual-google-submit"', false)
        ->assertDontSee('id="google-results"', false);
});
