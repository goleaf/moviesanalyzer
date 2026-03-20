<?php

use App\Models\MovieFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('shows a detailed movie page with local tmdb assets and rich metadata', function (): void {
    $movieFile = MovieFile::query()->create([
        'smb_path' => 'Movies/The.Matrix.1999.1080p.mkv',
        'filename' => 'The.Matrix.1999.1080p.mkv',
        'file_size_bytes' => 2_000_000_000,
        'extension' => 'mkv',
        'tmdb_id' => 603,
        'tmdb_title' => 'The Matrix',
        'tmdb_original_title' => 'The Matrix',
        'tmdb_year' => 1999,
        'movie_year' => 1999,
        'tmdb_runtime' => 136,
        'tmdb_release_date' => '1999-03-30',
        'tmdb_tagline' => 'Welcome to the Real World.',
        'tmdb_imdb_id' => 'tt0133093',
        'tmdb_vote_average' => 8.2,
        'tmdb_popularity' => 95.7,
        'tmdb_vote_count' => 25000,
        'tmdb_url' => 'https://www.themoviedb.org/movie/603',
        'tmdb_metadata' => [
            'genres' => ['Action', 'Science Fiction'],
            'keywords' => ['artificial reality', 'hacker'],
            'local_images' => [
                'posters' => [
                    [
                        'tmdb_file_path' => '/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg',
                        'local_path' => 'tmdb/movies/603/posters/poster-a.jpg',
                        'local_url' => 'https://moviesanalyzer.test/storage/tmdb/movies/603/posters/poster-a.jpg',
                    ],
                ],
                'backdrops' => [],
                'logos' => [],
            ],
            'full_payload' => [
                'budget' => 63000000,
                'revenue' => 467000000,
            ],
            'full_synced_at' => now()->toIso8601String(),
            'local_images_synced_at' => now()->toIso8601String(),
        ],
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
        'tmdb_original_title' => 'The Matrix',
        'tmdb_year' => 1999,
        'movie_year' => 1999,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $this->get(route('cineclean.movies.show', $movieFile))
        ->assertSuccessful()
        ->assertSee('Movie Details')
        ->assertSee('The Matrix')
        ->assertSee('Welcome to the Real World.')
        ->assertSee('https://moviesanalyzer.test/storage/tmdb/movies/603/posters/poster-a.jpg')
        ->assertSee('Sync Full TMDB Details')
        ->assertSee('Copies in Library');
});

it('renders details links on movies index cards', function (): void {
    $movieFile = MovieFile::query()->create([
        'smb_path' => 'Movies/The.Matrix.1999.1080p.mkv',
        'filename' => 'The.Matrix.1999.1080p.mkv',
        'file_size_bytes' => 2_000_000_000,
        'extension' => 'mkv',
        'tmdb_id' => 603,
        'tmdb_title' => 'The Matrix',
        'tmdb_original_title' => 'The Matrix',
        'tmdb_year' => 1999,
        'movie_year' => 1999,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $this->get(route('cineclean.movies.index'))
        ->assertSuccessful()
        ->assertSee(route('cineclean.movies.show', $movieFile));
});

it('syncs full tmdb details and downloads tmdb images locally for all movie copies', function (): void {
    config()->set('cineclean.tmdb.provider', 'http');
    config()->set('cineclean.tmdb.mcp_enabled', false);
    config()->set('cineclean.tmdb.preferred_language', 'en-US');
    config()->set('cineclean.tmdb.token', '');
    config()->set('cineclean.tmdb.api_key', 'demo-key');
    config()->set('cineclean.tmdb.image_disk', 'public');
    config()->set('cineclean.tmdb.image_directory', 'tmdb/movies');
    Storage::fake('public');

    $movieFile = MovieFile::query()->create([
        'smb_path' => 'Movies/The.Matrix.1999.1080p.mkv',
        'filename' => 'The.Matrix.1999.1080p.mkv',
        'file_size_bytes' => 2_000_000_000,
        'extension' => 'mkv',
        'tmdb_id' => 603,
        'tmdb_title' => 'The Matrix',
        'tmdb_original_title' => 'The Matrix',
        'tmdb_year' => 1999,
        'movie_year' => 1999,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    $copy = MovieFile::query()->create([
        'smb_path' => 'Movies/The.Matrix.1999.2160p.mkv',
        'filename' => 'The.Matrix.1999.2160p.mkv',
        'file_size_bytes' => 4_000_000_000,
        'extension' => 'mkv',
        'tmdb_id' => 603,
        'tmdb_title' => 'The Matrix',
        'tmdb_original_title' => 'The Matrix',
        'tmdb_year' => 1999,
        'movie_year' => 1999,
        'match_status' => 'matched',
        'scanned_at' => now(),
    ]);

    Http::fake(function (Request $request) {
        $url = $request->url();

        if (str_contains($url, '/configuration')) {
            return Http::response([
                'images' => [
                    'secure_base_url' => 'https://image.tmdb.org/t/p/',
                ],
            ], 200);
        }

        if (str_contains($url, '/movie/603')) {
            return Http::response([
                'id' => 603,
                'title' => 'The Matrix',
                'original_title' => 'The Matrix',
                'release_date' => '1999-03-30',
                'runtime' => 136,
                'status' => 'Released',
                'tagline' => 'Welcome to the Real World.',
                'imdb_id' => 'tt0133093',
                'poster_path' => '/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg',
                'overview' => 'A hacker discovers reality is a simulation.',
                'vote_average' => 8.2,
                'vote_count' => 25000,
                'popularity' => 95.7,
                'images' => [
                    'posters' => [
                        ['file_path' => '/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg', 'width' => 1000, 'height' => 1500],
                        ['file_path' => '/9TGHDvWrqKBzwDxDodHYXEmOE6J.jpg', 'width' => 1000, 'height' => 1500],
                    ],
                    'backdrops' => [],
                    'logos' => [],
                ],
                'budget' => 63000000,
                'revenue' => 467000000,
            ], 200);
        }

        if (str_contains($url, '/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg')) {
            return Http::response('poster-one', 200, ['Content-Type' => 'image/jpeg']);
        }

        if (str_contains($url, '/9TGHDvWrqKBzwDxDodHYXEmOE6J.jpg')) {
            return Http::response('poster-two', 200, ['Content-Type' => 'image/jpeg']);
        }

        return Http::response([], 404);
    });

    $this->post(route('cineclean.movies.sync-details', $movieFile))
        ->assertRedirect(route('cineclean.movies.show', $movieFile));

    $movieFile->refresh();
    $copy->refresh();

    expect(data_get($movieFile->tmdb_metadata, 'full_payload.budget'))->toBe(63000000)
        ->and(data_get($movieFile->tmdb_metadata, 'local_images.posters'))->toHaveCount(2)
        ->and((string) $movieFile->tmdb_poster_path)->toContain('/storage/tmdb/movies/603/posters/')
        ->and($copy->tmdb_tagline)->toBe('Welcome to the Real World.');

    Storage::disk('public')->assertExists('tmdb/movies/603/posters/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg');
    Storage::disk('public')->assertExists('tmdb/movies/603/posters/9TGHDvWrqKBzwDxDodHYXEmOE6J.jpg');
});
