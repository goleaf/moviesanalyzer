<?php

use App\Services\FilenameParser;
use App\Services\TmdbService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('returns russian-localized title for matched movies', function (): void {
    config()->set('cineclean.tmdb.provider', 'http');
    config()->set('cineclean.tmdb.mcp_enabled', false);
    config()->set('cineclean.tmdb.preferred_language', 'ru-RU');
    config()->set('cineclean.tmdb.token', '');
    config()->set('cineclean.tmdb.api_key', 'demo-key');

    Http::fake(function (Request $request) {
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
                    ],
                ],
            ], 200);
        }

        if (str_contains($url, '/movie/603') && str_contains($url, 'language=ru-RU')) {
            return Http::response([
                'id' => 603,
                'title' => 'Матрица',
                'original_title' => 'The Matrix',
                'release_date' => '1999-03-30',
                'vote_average' => 8.2,
                'overview' => 'Описание фильма',
                'poster_path' => '/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg',
            ], 200);
        }

        return Http::response(['results' => []], 200);
    });

    $parsedFilename = app(FilenameParser::class)->parse('The.Matrix.1999.1080p.BluRay.x264.mkv');
    $match = app(TmdbService::class)->match($parsedFilename);

    expect($match->title)->toBe('Матрица')
        ->and($match->releaseYear)->toBe(1999);
});

it('localizes search candidates to russian when fallback english search is used', function (): void {
    config()->set('cineclean.tmdb.provider', 'http');
    config()->set('cineclean.tmdb.mcp_enabled', false);
    config()->set('cineclean.tmdb.preferred_language', 'ru-RU');
    config()->set('cineclean.tmdb.token', '');
    config()->set('cineclean.tmdb.api_key', 'demo-key');

    Http::fake(function (Request $request) {
        $url = $request->url();

        if (str_contains($url, '/search/movie') && str_contains($url, 'language=ru-RU')) {
            return Http::response(['results' => []], 200);
        }

        if (str_contains($url, '/search/movie') && str_contains($url, 'language=en-US')) {
            return Http::response([
                'results' => [
                    [
                        'id' => 603,
                        'title' => 'The Matrix',
                        'original_title' => 'The Matrix',
                        'release_date' => '1999-03-30',
                        'vote_average' => 8.2,
                    ],
                ],
            ], 200);
        }

        if (str_contains($url, '/movie/603') && str_contains($url, 'language=ru-RU')) {
            return Http::response([
                'id' => 603,
                'title' => 'Матрица',
                'original_title' => 'The Matrix',
                'release_date' => '1999-03-30',
                'vote_average' => 8.2,
                'overview' => 'Описание фильма',
                'poster_path' => '/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg',
            ], 200);
        }

        return Http::response(['results' => []], 200);
    });

    $candidates = app(TmdbService::class)->searchCandidates('The Matrix');

    expect($candidates)->not->toBeEmpty()
        ->and($candidates[0]['title'])->toBe('Матрица')
        ->and($candidates[0]['release_year'])->toBe(1999);
});
