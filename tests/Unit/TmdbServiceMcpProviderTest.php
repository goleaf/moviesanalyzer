<?php

use App\Services\FilenameParser;
use App\Services\McpTmdbService;
use App\Services\TmdbService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('uses mcp provider for matching when configured as mcp_only', function (): void {
    config()->set('cineclean.tmdb.provider', 'mcp_only');
    config()->set('cineclean.tmdb.mcp_enabled', true);
    config()->set('cineclean.tmdb.preferred_language', 'ru-RU');

    Http::preventStrayRequests();

    $mcpTmdbService = Mockery::mock(McpTmdbService::class);
    $mcpTmdbService->shouldReceive('searchMovies')
        ->once()
        ->withArgs(function (string $query, string $language, ?int $year): bool {
            return str_contains(mb_strtolower($query, 'UTF-8'), 'matrix')
                && $language === 'ru-RU'
                && $year === 1999;
        })
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

    $mcpTmdbService->shouldReceive('findMovieById')
        ->once()
        ->with(603, 'ru-RU')
        ->andReturn([
            'tmdb_id' => 603,
            'title' => 'Матрица',
            'original_title' => 'The Matrix',
            'release_year' => 1999,
            'poster_path' => '/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg',
            'overview' => 'Описание фильма',
            'vote_average' => 8.2,
            'tmdb_url' => 'https://www.themoviedb.org/movie/603',
        ]);

    $this->app->instance(McpTmdbService::class, $mcpTmdbService);

    $parsedFilename = app(FilenameParser::class)->parse('The.Matrix.1999.1080p.BluRay.x264.mkv');
    $match = app(TmdbService::class)->match($parsedFilename);

    expect($match->tmdbId)->toBe(603)
        ->and($match->title)->toBe('Матрица')
        ->and($match->releaseYear)->toBe(1999);
});

it('falls back to http tmdb when mcp provider fails in mcp_with_http_fallback mode', function (): void {
    config()->set('cineclean.tmdb.provider', 'mcp_with_http_fallback');
    config()->set('cineclean.tmdb.mcp_enabled', true);
    config()->set('cineclean.tmdb.mcp_http_fallback', true);
    config()->set('cineclean.tmdb.preferred_language', 'ru-RU');
    config()->set('cineclean.tmdb.token', '');
    config()->set('cineclean.tmdb.api_key', 'demo-key');

    $mcpTmdbService = Mockery::mock(McpTmdbService::class);
    $mcpTmdbService->shouldReceive('searchMovies')
        ->once()
        ->andThrow(new RuntimeException('MCP server unavailable'));
    $mcpTmdbService->shouldReceive('findMovieById')->never();

    $this->app->instance(McpTmdbService::class, $mcpTmdbService);

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

    expect($match->tmdbId)->toBe(603)
        ->and($match->title)->toBe('Матрица')
        ->and($match->releaseYear)->toBe(1999);
});
