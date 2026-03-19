<?php

use App\Services\GoogleMovieResearchService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('returns configuration guidance when google assist is not configured', function (): void {
    config()->set('cineclean.google_assist.api_key', '');
    config()->set('cineclean.google_assist.cx', '');
    Http::fake([
        'https://api.themoviedb.org/3/search/movie*' => Http::response([
            'results' => [],
        ], 200),
    ]);

    $payload = app(GoogleMovieResearchService::class)->research('The.Matrix.1999.1080p.BluRay.x264.mkv');

    expect($payload['enabled'])->toBeFalse()
        ->and((string) $payload['message'])->toContain('GOOGLE_CSE_API_KEY');
});

it('extracts title suggestions from google and maps them to tmdb candidates', function (): void {
    config()->set('cineclean.google_assist.api_key', 'demo-key');
    config()->set('cineclean.google_assist.cx', 'demo-cx');
    config()->set('cineclean.google_assist.max_results', 5);

    Http::fake([
        'https://www.googleapis.com/customsearch/v1*' => Http::response([
            'items' => [
                [
                    'title' => 'The Matrix (1999) - IMDb',
                    'snippet' => 'A computer hacker learns the true nature of reality.',
                    'link' => 'https://www.imdb.com/title/tt0133093/',
                    'displayLink' => 'www.imdb.com',
                ],
                [
                    'title' => 'The Matrix — The Movie Database (TMDB)',
                    'snippet' => 'Visit TMDB page for The Matrix.',
                    'link' => 'https://www.themoviedb.org/movie/603-the-matrix',
                    'displayLink' => 'www.themoviedb.org',
                ],
            ],
        ], 200),
        'https://api.themoviedb.org/3/movie/603*' => Http::response([
            'id' => 603,
            'title' => 'The Matrix',
            'original_title' => 'The Matrix',
            'release_date' => '1999-03-30',
            'poster_path' => '/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg',
            'overview' => 'A hacker discovers reality is a simulation.',
            'vote_average' => 8.2,
        ], 200),
        'https://api.themoviedb.org/3/search/movie*' => Http::response([
            'results' => [
                [
                    'id' => 603,
                    'title' => 'The Matrix',
                    'original_title' => 'The Matrix',
                    'release_date' => '1999-03-30',
                    'poster_path' => '/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg',
                    'overview' => 'A hacker discovers reality is a simulation.',
                    'vote_average' => 8.2,
                ],
            ],
        ], 200),
    ]);

    $payload = app(GoogleMovieResearchService::class)->research('Matrix.Ultimate.Cut.1999.HDRip.mkv', 'matrix 1999 movie');

    expect($payload['enabled'])->toBeTrue()
        ->and($payload['query'])->toBe('matrix 1999 movie')
        ->and($payload['title_suggestions'])->toContain('The Matrix')
        ->and($payload['tmdb_candidates'])->not->toBeEmpty()
        ->and($payload['tmdb_candidates'][0]['tmdb_id'])->toBe(603);
});
