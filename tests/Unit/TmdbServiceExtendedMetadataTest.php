<?php

use App\Services\TmdbService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('requests extended tmdb details and normalizes metadata fields', function (): void {
    config()->set('cineclean.tmdb.provider', 'http');
    config()->set('cineclean.tmdb.mcp_enabled', false);
    config()->set('cineclean.tmdb.preferred_language', 'ru-RU');
    config()->set('cineclean.tmdb.token', '');
    config()->set('cineclean.tmdb.api_key', 'demo-key');

    $capturedMovieDetailsUrl = null;

    Http::fake(function (Request $request) use (&$capturedMovieDetailsUrl) {
        $url = $request->url();

        if (str_contains($url, '/search/movie') && str_contains($url, 'language=ru-RU')) {
            return Http::response([
                'results' => [
                    [
                        'id' => 603,
                        'title' => 'Матрица',
                        'original_title' => 'The Matrix',
                        'release_date' => '1999-03-30',
                        'vote_average' => 8.2,
                        'vote_count' => 25000,
                        'original_language' => 'en',
                        'popularity' => 95.7,
                    ],
                ],
            ], 200);
        }

        if (str_contains($url, '/movie/603') && str_contains($url, 'language=ru-RU')) {
            $capturedMovieDetailsUrl = $url;

            return Http::response([
                'id' => 603,
                'title' => 'Матрица',
                'original_title' => 'The Matrix',
                'release_date' => '1999-03-30',
                'vote_average' => 8.2,
                'vote_count' => 25000,
                'runtime' => 136,
                'status' => 'Released',
                'tagline' => 'Welcome to the Real World.',
                'imdb_id' => 'tt0133093',
                'original_language' => 'en',
                'popularity' => 95.7,
                'overview' => 'Описание фильма',
                'poster_path' => '/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg',
                'genres' => [
                    ['id' => 28, 'name' => 'Action'],
                    ['id' => 878, 'name' => 'Science Fiction'],
                ],
                'production_countries' => [
                    ['iso_3166_1' => 'US', 'name' => 'United States of America'],
                ],
                'spoken_languages' => [
                    ['iso_639_1' => 'en', 'english_name' => 'English', 'name' => 'English'],
                ],
                'keywords' => [
                    'keywords' => [
                        ['id' => 101, 'name' => 'artificial reality'],
                        ['id' => 102, 'name' => 'hacker'],
                    ],
                ],
                'videos' => [
                    'results' => [
                        ['site' => 'YouTube', 'type' => 'Trailer', 'key' => 'm8e-FF8MsqU', 'official' => true],
                    ],
                ],
                'watch/providers' => [
                    'results' => [
                        'US' => [
                            'flatrate' => [
                                ['provider_id' => 8, 'provider_name' => 'Netflix'],
                            ],
                        ],
                    ],
                ],
                'release_dates' => [
                    'results' => [
                        [
                            'iso_3166_1' => 'US',
                            'release_dates' => [
                                ['certification' => 'R', 'release_date' => '1999-03-30T00:00:00.000Z'],
                            ],
                        ],
                    ],
                ],
            ], 200);
        }

        return Http::response(['results' => []], 200);
    });

    $candidates = app(TmdbService::class)->searchCandidates('The Matrix', 1999);

    expect($capturedMovieDetailsUrl)->not->toBeNull()
        ->and($capturedMovieDetailsUrl)->toContain('append_to_response=')
        ->and($capturedMovieDetailsUrl)->toContain('external_ids')
        ->and($candidates)->toHaveCount(1)
        ->and($candidates[0]['tmdb_imdb_id'])->toBe('tt0133093')
        ->and($candidates[0]['tmdb_runtime'])->toBe(136)
        ->and($candidates[0]['tmdb_vote_count'])->toBe(25000)
        ->and($candidates[0]['tmdb_original_language'])->toBe('en')
        ->and($candidates[0]['tmdb_metadata'])->toBeArray()
        ->and($candidates[0]['tmdb_metadata']['genres'])->toBe(['Action', 'Science Fiction'])
        ->and($candidates[0]['tmdb_metadata']['keywords'])->toBe(['artificial reality', 'hacker']);
});
