<?php

use App\Services\McpTmdbService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

uses(TestCase::class);

it('searches movies via mcp tool and normalizes result payload', function (): void {
    Cache::flush();

    config()->set('cineclean.tmdb.mcp_enabled', true);
    config()->set('cineclean.tmdb.mcp_command', 'uvx mcp-server-tmdb');
    config()->set('cineclean.tmdb.mcp_search_tools', 'search_movies');
    config()->set('cineclean.tmdb.mcp_movie_tools', 'get_movie_details');

    Process::fake(function ($process) {
        $input = (string) ($process->input ?? '');

        if (str_contains($input, '"method":"tools/list"')) {
            return Process::result(output: json_encode([
                'jsonrpc' => '2.0',
                'id' => 2,
                'result' => [
                    'tools' => [
                        ['name' => 'search_movies'],
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        if (str_contains($input, '"method":"tools/call"')) {
            return Process::result(output: json_encode([
                'jsonrpc' => '2.0',
                'id' => 2,
                'result' => [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => json_encode([
                                [
                                    'id' => 603,
                                    'title' => 'Матрица',
                                    'original_title' => 'The Matrix',
                                    'release_date' => '1999-03-30',
                                    'vote_average' => 8.2,
                                    'poster_path' => '/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg',
                                    'overview' => 'Описание фильма',
                                ],
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ],
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return Process::result(output: '');
    });

    $movies = app(McpTmdbService::class)->searchMovies('The Matrix', 'ru-RU', 1999);

    expect($movies)->toHaveCount(1)
        ->and($movies[0]['tmdb_id'])->toBe(603)
        ->and($movies[0]['title'])->toBe('Матрица')
        ->and($movies[0]['release_year'])->toBe(1999)
        ->and($movies[0]['tmdb_url'])->toBe('https://www.themoviedb.org/movie/603');
});

it('reads movie details via mcp resource uri', function (): void {
    Cache::flush();

    config()->set('cineclean.tmdb.mcp_enabled', true);
    config()->set('cineclean.tmdb.mcp_command', 'uvx mcp-server-tmdb-resources');
    config()->set('cineclean.tmdb.mcp_resource_uris', 'tmdb://movie/{id}');

    Process::fake(function ($process) {
        $input = (string) ($process->input ?? '');

        if (str_contains($input, '"method":"resources/read"')) {
            return Process::result(output: json_encode([
                'jsonrpc' => '2.0',
                'id' => 2,
                'result' => [
                    'contents' => [
                        [
                            'uri' => 'tmdb://movie/603',
                            'text' => json_encode([
                                'id' => 603,
                                'title' => 'Матрица',
                                'original_title' => 'The Matrix',
                                'release_date' => '1999-03-30',
                                'vote_average' => 8.2,
                                'overview' => 'Описание фильма',
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ],
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return Process::result(output: '');
    });

    $movie = app(McpTmdbService::class)->findMovieById(603, 'ru-RU');

    expect($movie)->not->toBeNull()
        ->and($movie['tmdb_id'])->toBe(603)
        ->and($movie['title'])->toBe('Матрица')
        ->and($movie['release_year'])->toBe(1999);
});

it('throws a clear error when tmdb mcp is not configured', function (): void {
    config()->set('cineclean.tmdb.mcp_enabled', false);
    config()->set('cineclean.tmdb.mcp_command', '');

    expect(fn (): array => app(McpTmdbService::class)->searchMovies('Matrix', 'en-US'))
        ->toThrow(RuntimeException::class, 'TMDB_MCP_ENABLED');
});
