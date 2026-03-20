<?php

use App\Data\ParsedFilename;
use App\Data\TmdbMatch;
use App\Enums\MatchStatus;
use App\Models\MovieFile;
use App\Models\ScanLog;
use App\Services\GoogleMovieResearchService;
use App\Services\SmbService;
use App\Services\TmdbService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deletes only the selected file by explicit request', function (): void {
    config()->set('cineclean.files.allow_delete', true);

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
        ->and($movieFile->tmdb_title)->toBe('The Matrix')
        ->and($movieFile->movie_year)->toBe(1999);
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

it('runs google assist research for unmatched files and returns tmdb candidates for user approval', function (): void {
    $movieFile = MovieFile::query()->create([
        'smb_path' => 'Movies/Matrix.Ultimate.Cut.1999.HDRip.mkv',
        'filename' => 'Matrix.Ultimate.Cut.1999.HDRip.mkv',
        'file_size_bytes' => 800_000_000,
        'extension' => 'mkv',
        'match_status' => 'unmatched',
        'scanned_at' => now(),
    ]);

    $googleMovieResearchService = Mockery::mock(GoogleMovieResearchService::class);
    $googleMovieResearchService->shouldReceive('research')
        ->once()
        ->with('Matrix.Ultimate.Cut.1999.HDRip.mkv', 'matrix 1999 movie')
        ->andReturn([
            'enabled' => true,
            'provider' => 'mcp_google_fetch',
            'query' => 'matrix 1999 movie',
            'default_query' => 'Matrix movie',
            'parsed_clean_title' => 'Matrix',
            'title_suggestions' => ['The Matrix'],
            'google_results' => [],
            'tmdb_candidates' => [
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
            ],
            'message' => null,
        ]);

    $this->app->instance(GoogleMovieResearchService::class, $googleMovieResearchService);

    $this->postJson(route('cineclean.unmatched.google-assist', $movieFile), [
        'query' => 'matrix 1999 movie',
    ])
        ->assertSuccessful()
        ->assertJsonPath('query', 'matrix 1999 movie')
        ->assertJsonPath('title_suggestions.0', 'The Matrix')
        ->assertJsonPath('tmdb_candidates.0.tmdb_id', 603);
});

it('rescans all unmatched files with current parser rules and updates matches', function (): void {
    $movieFile = MovieFile::query()->create([
        'smb_path' => 'Movies/The.Matrix.1999.1080p.BluRay.x264.mkv',
        'filename' => 'The.Matrix.1999.1080p.BluRay.x264.mkv',
        'file_size_bytes' => 1_500_000_000,
        'extension' => 'mkv',
        'match_status' => MatchStatus::Unmatched,
        'scanned_at' => now()->subDay(),
    ]);

    $tmdbService = Mockery::mock(TmdbService::class);
    $tmdbService->shouldReceive('match')
        ->once()
        ->with(Mockery::type(ParsedFilename::class))
        ->andReturn(TmdbMatch::fromMovie([
            'tmdb_id' => 603,
            'title' => 'The Matrix',
            'original_title' => 'The Matrix',
            'release_year' => 1999,
            'poster_path' => '/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg',
            'overview' => 'A hacker discovers reality is a simulation.',
            'vote_average' => 8.2,
            'tmdb_url' => 'https://www.themoviedb.org/movie/603',
        ], MatchStatus::Matched, 0.99));

    $this->app->instance(TmdbService::class, $tmdbService);

    $this->post(route('cineclean.unmatched.rescan'))
        ->assertRedirect(route('cineclean.unmatched.index'));

    $movieFile->refresh();

    expect($movieFile->match_status)->toBe(MatchStatus::Matched)
        ->and($movieFile->parsed_clean_title)->toBe('The Matrix')
        ->and($movieFile->tmdb_id)->toBe(603)
        ->and($movieFile->movie_year)->toBe(1999)
        ->and(
            ScanLog::query()
                ->where('notes', 'like', 'rescan_unmatched:%')
                ->exists()
        )->toBeTrue();
});

it('rescans one unmatched file from the page and returns status payload', function (): void {
    $movieFile = MovieFile::query()->create([
        'smb_path' => 'Movies/The.Matrix.1999.1080p.BluRay.x264.mkv',
        'filename' => 'The.Matrix.1999.1080p.BluRay.x264.mkv',
        'file_size_bytes' => 1_500_000_000,
        'extension' => 'mkv',
        'match_status' => MatchStatus::Unmatched,
        'scanned_at' => now()->subDay(),
    ]);

    $tmdbService = Mockery::mock(TmdbService::class);
    $tmdbService->shouldReceive('match')
        ->once()
        ->with(Mockery::type(ParsedFilename::class))
        ->andReturn(TmdbMatch::fromMovie([
            'tmdb_id' => 603,
            'title' => 'The Matrix',
            'original_title' => 'The Matrix',
            'release_year' => 1999,
            'poster_path' => '/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg',
            'overview' => 'A hacker discovers reality is a simulation.',
            'vote_average' => 8.2,
            'tmdb_url' => 'https://www.themoviedb.org/movie/603',
        ], MatchStatus::Matched, 0.99));

    $this->app->instance(TmdbService::class, $tmdbService);

    $this->postJson(route('cineclean.unmatched.refresh', $movieFile))
        ->assertSuccessful()
        ->assertJsonPath('movie_file_id', $movieFile->id)
        ->assertJsonPath('status', 'matched')
        ->assertJsonPath('tmdb_id', 603)
        ->assertJsonPath('tmdb_title', 'The Matrix')
        ->assertJsonPath('failed', false);

    $movieFile->refresh();

    expect($movieFile->match_status)->toBe(MatchStatus::Matched)
        ->and($movieFile->parsed_clean_title)->toBe('The Matrix')
        ->and($movieFile->tmdb_id)->toBe(603);
});
