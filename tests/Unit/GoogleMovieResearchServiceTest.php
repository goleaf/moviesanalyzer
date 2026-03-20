<?php

use App\Services\GoogleMovieResearchService;
use App\Services\McpFetchService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('returns configuration guidance when google assist is not configured', function (): void {
    $mcpFetchService = Mockery::mock(McpFetchService::class);
    $mcpFetchService->shouldReceive('isConfigured')->andReturnFalse();
    $this->app->instance(McpFetchService::class, $mcpFetchService);

    Http::fake([
        'https://api.themoviedb.org/3/search/movie*' => Http::response([
            'results' => [],
        ], 200),
    ]);

    $payload = app(GoogleMovieResearchService::class)->research('The.Matrix.1999.1080p.BluRay.x264.mkv');

    expect($payload['enabled'])->toBeFalse()
        ->and((string) $payload['message'])->toContain('GOOGLE_ASSIST_MCP_COMMAND');
});

it('extracts title suggestions from google and maps them to tmdb candidates', function (): void {
    config()->set('cineclean.google_assist.mcp_command', 'uvx mcp-server-fetch');
    config()->set('cineclean.google_assist.max_results', 5);

    $mcpFetchService = Mockery::mock(McpFetchService::class);
    $mcpFetchService->shouldReceive('isConfigured')->andReturnTrue();
    $mcpFetchService->shouldReceive('fetch')
        ->once()
        ->andReturn(<<<'MD'
[The Matrix (1999) - IMDb](https://www.imdb.com/title/tt0133093/)
A computer hacker learns the true nature of reality.
[The Matrix — The Movie Database (TMDB)](https://www.themoviedb.org/movie/603-the-matrix)
Visit TMDB page for The Matrix.
MD);
    $this->app->instance(McpFetchService::class, $mcpFetchService);

    Http::fake([
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
        ->and($payload['provider'])->toBe('mcp_google_fetch')
        ->and($payload['query'])->toBe('matrix 1999 movie')
        ->and($payload['title_suggestions'])->toContain('The Matrix')
        ->and($payload['tmdb_candidates'])->not->toBeEmpty()
        ->and($payload['tmdb_candidates'][0]['tmdb_id'])->toBe(603);
});
