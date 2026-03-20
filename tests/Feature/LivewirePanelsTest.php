<?php

use App\Livewire\Duplicates\ConflictCenterPanel;
use App\Models\MovieFile;
use App\Services\SmbService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders livewire dashboard operations center', function (): void {
    $this->get(route('cineclean.dashboard'))
        ->assertSuccessful()
        ->assertSee('Operations Center')
        ->assertSee('SCAN LIBRARY');
});

it('filters movie explorer to duplicate-only groups through url query state', function (): void {
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

    MovieFile::query()->create([
        'smb_path' => 'Movies/Inception.2010.1080p.mkv',
        'filename' => 'Inception.2010.1080p.mkv',
        'file_size_bytes' => 3_000_000_000,
        'extension' => 'mkv',
        'tmdb_id' => 27205,
        'tmdb_title' => 'Inception',
        'tmdb_year' => 2010,
        'movie_year' => 2010,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $this->get(route('cineclean.movies.index', ['dupes' => 1]))
        ->assertSuccessful()
        ->assertSee('The Matrix')
        ->assertDontSee('Inception');
});

it('deletes file records from livewire duplicate conflict center', function (): void {
    config()->set('cineclean.files.allow_delete', true);

    $movieFile = MovieFile::query()->create([
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

    $smbService = Mockery::mock(SmbService::class);
    $smbService->shouldReceive('deleteFile')
        ->once()
        ->with('Movies/The.Matrix.1999.1080p.mkv')
        ->andReturnTrue();

    $this->app->instance(SmbService::class, $smbService);

    Livewire::test(ConflictCenterPanel::class)
        ->call('deleteFile', $movieFile->id)
        ->assertDispatched('notify');

    expect(MovieFile::query()->whereKey($movieFile->id)->exists())->toBeFalse();
});

it('auto-selects non-mp4 files when duplicate group contains an mp4 file', function (): void {
    config()->set('cineclean.files.allow_delete', true);

    $mp4File = MovieFile::query()->create([
        'smb_path' => 'Movies/The.Matrix.1999.Remux.mp4',
        'filename' => 'The.Matrix.1999.Remux.mp4',
        'file_size_bytes' => 5_000_000_000,
        'extension' => 'mp4',
        'tmdb_id' => 603,
        'tmdb_title' => 'The Matrix',
        'tmdb_year' => 1999,
        'movie_year' => 1999,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $mkvFile = MovieFile::query()->create([
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

    $srtFile = MovieFile::query()->create([
        'smb_path' => 'Movies/The.Matrix.1999.en.srt',
        'filename' => 'The.Matrix.1999.en.srt',
        'file_size_bytes' => 2_000_000,
        'extension' => 'srt',
        'tmdb_id' => 603,
        'tmdb_title' => 'The Matrix',
        'tmdb_year' => 1999,
        'movie_year' => 1999,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $selected = Livewire::test(ConflictCenterPanel::class)->get('selectedFileIds');
    $selectedIds = collect($selected)
        ->map(static fn (int|string $value): int => (int) $value)
        ->sort()
        ->values()
        ->all();

    expect($selectedIds)->toBe([$mkvFile->id, $srtFile->id])
        ->not()->toContain($mp4File->id);
});

it('auto-selects smaller mp4 files when duplicate group contains multiple mp4 files', function (): void {
    config()->set('cineclean.files.allow_delete', true);

    $largestMp4File = MovieFile::query()->create([
        'smb_path' => 'Movies/Gladiator.2000.Remux.mp4',
        'filename' => 'Gladiator.2000.Remux.mp4',
        'file_size_bytes' => 8_000_000_000,
        'extension' => 'mp4',
        'tmdb_id' => 98,
        'tmdb_title' => 'Gladiator',
        'tmdb_year' => 2000,
        'movie_year' => 2000,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $smallerMp4File = MovieFile::query()->create([
        'smb_path' => 'Movies/Gladiator.2000.1080p.mp4',
        'filename' => 'Gladiator.2000.1080p.mp4',
        'file_size_bytes' => 4_000_000_000,
        'extension' => 'mp4',
        'tmdb_id' => 98,
        'tmdb_title' => 'Gladiator',
        'tmdb_year' => 2000,
        'movie_year' => 2000,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $mkvFile = MovieFile::query()->create([
        'smb_path' => 'Movies/Gladiator.2000.1080p.mkv',
        'filename' => 'Gladiator.2000.1080p.mkv',
        'file_size_bytes' => 3_000_000_000,
        'extension' => 'mkv',
        'tmdb_id' => 98,
        'tmdb_title' => 'Gladiator',
        'tmdb_year' => 2000,
        'movie_year' => 2000,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $selected = Livewire::test(ConflictCenterPanel::class)->get('selectedFileIds');
    $selectedIds = collect($selected)
        ->map(static fn (int|string $value): int => (int) $value)
        ->sort()
        ->values()
        ->all();
    $expectedSelectedIds = collect([$mkvFile->id, $smallerMp4File->id])
        ->sort()
        ->values()
        ->all();

    expect($selectedIds)->toBe($expectedSelectedIds)
        ->not()->toContain($largestMp4File->id);
});

it('auto-selects all but largest file when duplicate group has no mp4 files', function (): void {
    config()->set('cineclean.files.allow_delete', true);

    $largestFile = MovieFile::query()->create([
        'smb_path' => 'Movies/Inception.2010.1080p.mkv',
        'filename' => 'Inception.2010.1080p.mkv',
        'file_size_bytes' => 3_000_000_000,
        'extension' => 'mkv',
        'tmdb_id' => 27205,
        'tmdb_title' => 'Inception',
        'tmdb_year' => 2010,
        'movie_year' => 2010,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $mediumFile = MovieFile::query()->create([
        'smb_path' => 'Movies/Inception.2010.720p.avi',
        'filename' => 'Inception.2010.720p.avi',
        'file_size_bytes' => 2_000_000_000,
        'extension' => 'avi',
        'tmdb_id' => 27205,
        'tmdb_title' => 'Inception',
        'tmdb_year' => 2010,
        'movie_year' => 2010,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $smallestFile = MovieFile::query()->create([
        'smb_path' => 'Movies/Inception.2010.low.m4v',
        'filename' => 'Inception.2010.low.m4v',
        'file_size_bytes' => 900_000_000,
        'extension' => 'm4v',
        'tmdb_id' => 27205,
        'tmdb_title' => 'Inception',
        'tmdb_year' => 2010,
        'movie_year' => 2010,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    Livewire::test(ConflictCenterPanel::class)
        ->assertSet('selectedFileIds', [$mediumFile->id, $smallestFile->id]);

    expect($largestFile->id)->not->toBe($mediumFile->id);
    expect($largestFile->id)->not->toBe($smallestFile->id);
});

it('bulk deletes selected files one by one and leaves mp4 files untouched', function (): void {
    config()->set('cineclean.files.allow_delete', true);

    $mp4File = MovieFile::query()->create([
        'smb_path' => 'Movies/Interstellar.2014.Remux.mp4',
        'filename' => 'Interstellar.2014.Remux.mp4',
        'file_size_bytes' => 8_000_000_000,
        'extension' => 'mp4',
        'tmdb_id' => 157336,
        'tmdb_title' => 'Interstellar',
        'tmdb_year' => 2014,
        'movie_year' => 2014,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $mkvFile = MovieFile::query()->create([
        'smb_path' => 'Movies/Interstellar.2014.1080p.mkv',
        'filename' => 'Interstellar.2014.1080p.mkv',
        'file_size_bytes' => 4_000_000_000,
        'extension' => 'mkv',
        'tmdb_id' => 157336,
        'tmdb_title' => 'Interstellar',
        'tmdb_year' => 2014,
        'movie_year' => 2014,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $srtFile = MovieFile::query()->create([
        'smb_path' => 'Movies/Interstellar.2014.en.srt',
        'filename' => 'Interstellar.2014.en.srt',
        'file_size_bytes' => 3_000_000,
        'extension' => 'srt',
        'tmdb_id' => 157336,
        'tmdb_title' => 'Interstellar',
        'tmdb_year' => 2014,
        'movie_year' => 2014,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $smbService = Mockery::mock(SmbService::class);
    $smbService->shouldReceive('deleteFile')
        ->twice()
        ->withArgs(static fn (string $path): bool => in_array($path, [
            'Movies/Interstellar.2014.1080p.mkv',
            'Movies/Interstellar.2014.en.srt',
        ], true))
        ->andReturnTrue();

    $this->app->instance(SmbService::class, $smbService);

    $component = Livewire::test(ConflictCenterPanel::class);
    $component->call('startBulkDelete')
        ->assertSet('isBulkDeleting', true)
        ->assertSet('bulkDeleteTotal', 2)
        ->assertSet('bulkDeleteProcessed', 0);

    $component->call('processBulkDeletion')
        ->assertSet('bulkDeleteProcessed', 1);

    $component->call('processBulkDeletion')
        ->assertSet('bulkDeleteProcessed', 2)
        ->assertSet('isBulkDeleting', false);

    expect(MovieFile::query()->whereKey($mp4File->id)->exists())->toBeTrue();
    expect(MovieFile::query()->whereKey($mkvFile->id)->exists())->toBeFalse();
    expect(MovieFile::query()->whereKey($srtFile->id)->exists())->toBeFalse();
});

it('marks bulk delete item as failed when smb delete throws timeout-like exception', function (): void {
    config()->set('cineclean.files.allow_delete', true);

    MovieFile::query()->create([
        'smb_path' => 'Movies/Arrival.2016.Remux.mp4',
        'filename' => 'Arrival.2016.Remux.mp4',
        'file_size_bytes' => 7_000_000_000,
        'extension' => 'mp4',
        'tmdb_id' => 329865,
        'tmdb_title' => 'Arrival',
        'tmdb_year' => 2016,
        'movie_year' => 2016,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $mkvFile = MovieFile::query()->create([
        'smb_path' => 'Movies/Arrival.2016.1080p.mkv',
        'filename' => 'Arrival.2016.1080p.mkv',
        'file_size_bytes' => 4_000_000_000,
        'extension' => 'mkv',
        'tmdb_id' => 329865,
        'tmdb_title' => 'Arrival',
        'tmdb_year' => 2016,
        'movie_year' => 2016,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $smbService = Mockery::mock(SmbService::class);
    $smbService->shouldReceive('deleteFile')
        ->once()
        ->with('Movies/Arrival.2016.1080p.mkv')
        ->andThrow(new RuntimeException('SMB command timed out after 27 seconds.'));

    $this->app->instance(SmbService::class, $smbService);

    $component = Livewire::test(ConflictCenterPanel::class);
    $component->call('startBulkDelete')
        ->assertSet('isBulkDeleting', true);

    $component->call('processBulkDeletion')
        ->assertSet('bulkDeleteFailed', 1)
        ->assertSet('isBulkDeleting', false)
        ->assertDispatched('notify');

    expect(MovieFile::query()->whereKey($mkvFile->id)->exists())->toBeTrue();
});
