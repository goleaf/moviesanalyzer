<?php

use App\Models\MovieFile;
use App\Services\SmbService;
use App\Services\TmdbService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

uses(RefreshDatabase::class);

it('deletes only the selected file by explicit request', function (): void {
    $movieFile = MovieFile::query()->create([
        'smb_path' => 'Movies/Matrix/The.Matrix.1999.mkv',
        'filename' => 'The.Matrix.1999.mkv',
        'file_size_bytes' => 4_000_000_000,
        'extension' => 'mkv',
        'tmdb_id' => 603,
        'tmdb_title' => 'The Matrix',
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $smbService = Mockery::mock(SmbService::class);
    $smbService->shouldReceive('deleteFile')
        ->once()
        ->with('Movies/Matrix/The.Matrix.1999.mkv')
        ->andReturnTrue();

    $this->app->instance(SmbService::class, $smbService);

    $this->deleteJson(route('cineclean.file.destroy', $movieFile))
        ->assertSuccessful()
        ->assertJson([
            'deleted' => true,
        ]);

    expect(MovieFile::query()->whereKey($movieFile->id)->exists())->toBeFalse();
});

it('searches and applies manual tmdb matches for unmatched files', function (): void {
    $movieFile = MovieFile::query()->create([
        'smb_path' => 'Movies/матрица.avi',
        'filename' => 'матрица.avi',
        'file_size_bytes' => 1_400_000_000,
        'extension' => 'avi',
        'match_status' => 'unmatched',
        'scanned_at' => now(),
    ]);

    $tmdbService = Mockery::mock(TmdbService::class);
    $tmdbService->shouldReceive('searchCandidates')
        ->once()
        ->with('The Matrix')
        ->andReturn([
            [
                'tmdb_id' => 603,
                'title' => 'The Matrix',
                'original_title' => 'The Matrix',
                'release_year' => 1999,
                'poster_path' => '/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg',
                'overview' => 'A hacker discovers reality is a simulation.',
                'vote_average' => 8.2,
                'tmdb_url' => 'https://www.themoviedb.org/movie/603',
            ],
        ]);

    $tmdbService->shouldReceive('findMovieById')
        ->once()
        ->with(603)
        ->andReturn([
            'tmdb_id' => 603,
            'title' => 'The Matrix',
            'original_title' => 'The Matrix',
            'release_year' => 1999,
            'poster_path' => '/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg',
            'overview' => 'A hacker discovers reality is a simulation.',
            'vote_average' => 8.2,
            'tmdb_url' => 'https://www.themoviedb.org/movie/603',
        ]);

    $this->app->instance(TmdbService::class, $tmdbService);

    $this->postJson(route('cineclean.unmatched.search', $movieFile), [
        'query' => 'The Matrix',
    ])->assertSuccessful()->assertJsonCount(1, 'data');

    $this->patchJson(route('cineclean.unmatched.match', $movieFile), [
        'tmdb_id' => 603,
    ])->assertSuccessful();

    $movieFile->refresh();

    expect($movieFile->match_status->value)->toBe('matched')
        ->and($movieFile->tmdb_id)->toBe(603)
        ->and($movieFile->tmdb_title)->toBe('The Matrix');
});

it('marks unmatched files as skipped when user chooses skip', function (): void {
    $movieFile = MovieFile::query()->create([
        'smb_path' => 'Movies/unknown-file.mkv',
        'filename' => 'unknown-file.mkv',
        'file_size_bytes' => 700_000_000,
        'extension' => 'mkv',
        'match_status' => 'unmatched',
        'scanned_at' => now(),
    ]);

    $this->patchJson(route('cineclean.unmatched.skip', $movieFile))
        ->assertSuccessful();

    $movieFile->refresh();

    expect($movieFile->match_status->value)->toBe('skipped');
});
