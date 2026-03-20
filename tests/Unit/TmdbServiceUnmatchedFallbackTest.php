<?php

use App\Data\ParsedFilename;
use App\Enums\MatchStatus;
use App\Services\TmdbService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('retries automatic matching with progressively trimmed title tokens', function (): void {
    config()->set('cineclean.tmdb.provider', 'http');
    config()->set('cineclean.tmdb.mcp_enabled', false);
    config()->set('cineclean.tmdb.preferred_language', 'en-US');
    config()->set('cineclean.tmdb.token', '');
    config()->set('cineclean.tmdb.api_key', 'demo-key');

    $searchQueries = [];

    Http::fake(function (Request $request) use (&$searchQueries) {
        $url = $request->url();

        if (str_contains($url, '/search/movie')) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $params);
            $query = (string) ($params['query'] ?? '');
            $searchQueries[] = $query;

            if ($query === 'The Matrix Reloaded') {
                return Http::response([
                    'results' => [
                        [
                            'id' => 604,
                            'title' => 'The Matrix Reloaded',
                            'original_title' => 'The Matrix Reloaded',
                            'release_date' => '2003-05-15',
                            'vote_average' => 7.2,
                        ],
                    ],
                ], 200);
            }

            return Http::response(['results' => []], 200);
        }

        if (str_contains($url, '/movie/604')) {
            return Http::response([
                'id' => 604,
                'title' => 'The Matrix Reloaded',
                'original_title' => 'The Matrix Reloaded',
                'release_date' => '2003-05-15',
                'vote_average' => 7.2,
                'overview' => 'Reloaded overview',
                'poster_path' => '/9TGHDvWrqKBzwDxDodHYXEmOE6J.jpg',
            ], 200);
        }

        return Http::response(['results' => []], 200);
    });

    $parsedFilename = new ParsedFilename(
        originalFilename: 'The.Matrix.Reloaded.Director.Cut.2003.mkv',
        baseName: 'The.Matrix.Reloaded.Director.Cut.2003',
        cleanTitle: 'The Matrix Reloaded Director Cut',
        releaseYear: 2003,
        searchQueries: ['The Matrix Reloaded Director Cut'],
    );

    $match = app(TmdbService::class)->match($parsedFilename);

    expect($match->tmdbId)->toBe(604)
        ->and($searchQueries)->toContain('The Matrix Reloaded Director Cut')
        ->and($searchQueries)->toContain('The Matrix Reloaded');
});

it('requires exact release-year match for automatic matching', function (): void {
    config()->set('cineclean.tmdb.provider', 'http');
    config()->set('cineclean.tmdb.mcp_enabled', false);
    config()->set('cineclean.tmdb.preferred_language', 'en-US');
    config()->set('cineclean.tmdb.token', '');
    config()->set('cineclean.tmdb.api_key', 'demo-key');

    Http::fake(function (Request $request) {
        $url = $request->url();

        if (str_contains($url, '/search/movie')) {
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

        return Http::response(['results' => []], 200);
    });

    $parsedFilename = new ParsedFilename(
        originalFilename: 'The.Matrix.2001.1080p.mkv',
        baseName: 'The.Matrix.2001.1080p',
        cleanTitle: 'The Matrix',
        releaseYear: 2001,
        searchQueries: ['The Matrix'],
    );

    $match = app(TmdbService::class)->match($parsedFilename);

    expect($match->status)->toBe(MatchStatus::Unmatched)
        ->and($match->tmdbId)->toBeNull();
});

it('returns only exact-year tmdb candidates when year is provided', function (): void {
    config()->set('cineclean.tmdb.provider', 'http');
    config()->set('cineclean.tmdb.mcp_enabled', false);
    config()->set('cineclean.tmdb.preferred_language', 'en-US');
    config()->set('cineclean.tmdb.token', '');
    config()->set('cineclean.tmdb.api_key', 'demo-key');

    Http::fake(function (Request $request) {
        $url = $request->url();

        if (str_contains($url, '/search/movie')) {
            return Http::response([
                'results' => [
                    [
                        'id' => 603,
                        'title' => 'The Matrix',
                        'original_title' => 'The Matrix',
                        'release_date' => '1999-03-30',
                        'vote_average' => 8.2,
                    ],
                    [
                        'id' => 604,
                        'title' => 'The Matrix Reloaded',
                        'original_title' => 'The Matrix Reloaded',
                        'release_date' => '2003-05-15',
                        'vote_average' => 7.2,
                    ],
                ],
            ], 200);
        }

        if (str_contains($url, '/movie/604')) {
            return Http::response([
                'id' => 604,
                'title' => 'The Matrix Reloaded',
                'original_title' => 'The Matrix Reloaded',
                'release_date' => '2003-05-15',
                'vote_average' => 7.2,
            ], 200);
        }

        if (str_contains($url, '/movie/603')) {
            return Http::response([
                'id' => 603,
                'title' => 'The Matrix',
                'original_title' => 'The Matrix',
                'release_date' => '1999-03-30',
                'vote_average' => 8.2,
            ], 200);
        }

        return Http::response(['results' => []], 200);
    });

    $candidates = app(TmdbService::class)->searchCandidates('The Matrix', 2003);

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]['tmdb_id'])->toBe(604)
        ->and($candidates[0]['release_year'])->toBe(2003);
});
