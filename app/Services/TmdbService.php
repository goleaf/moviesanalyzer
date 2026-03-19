<?php

namespace App\Services;

use App\Data\ParsedFilename;
use App\Data\TmdbMatch;
use App\Enums\MatchStatus;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TmdbService
{
    /**
     * @var array<int, float>
     */
    private array $requestTimestamps = [];

    public function match(ParsedFilename $parsedFilename): TmdbMatch
    {
        $candidates = collect();

        foreach ($parsedFilename->searchQueries as $query) {
            $englishResults = $this->searchMovie($query, 'en-US');
            $candidates = $candidates->merge($englishResults);

            if ($englishResults === [] || preg_match('/\p{Cyrillic}/u', $query) === 1) {
                $candidates = $candidates->merge($this->searchMovie($query, 'ru-RU'));
            }
        }

        $uniqueCandidates = $candidates
            ->filter(fn (array $candidate): bool => isset($candidate['tmdb_id']))
            ->keyBy('tmdb_id')
            ->values();

        if ($uniqueCandidates->isEmpty()) {
            return TmdbMatch::unmatched();
        }

        $bestCandidate = null;
        $bestConfidence = 0.0;

        foreach ($uniqueCandidates as $candidate) {
            $confidence = $this->calculateConfidence($parsedFilename, $candidate);

            if ($confidence > $bestConfidence) {
                $bestConfidence = $confidence;
                $bestCandidate = $candidate;
            }
        }

        if ($bestCandidate === null) {
            return TmdbMatch::unmatched();
        }

        $matchThreshold = (float) config('cineclean.tmdb.match_threshold', 0.75);
        $uncertainThreshold = (float) config('cineclean.tmdb.uncertain_threshold', 0.55);

        if ($bestConfidence >= $matchThreshold) {
            return TmdbMatch::fromMovie($bestCandidate, MatchStatus::Matched, $bestConfidence);
        }

        if ($bestConfidence >= $uncertainThreshold) {
            return TmdbMatch::fromMovie($bestCandidate, MatchStatus::Uncertain, $bestConfidence);
        }

        return TmdbMatch::unmatched();
    }

    /**
     * @return array<int, array<string, float|int|string|null>>
     */
    public function searchCandidates(string $query): array
    {
        $english = $this->searchMovie($query, 'en-US');

        if ($english !== [] || preg_match('/\p{Cyrillic}/u', $query) !== 1) {
            return $english;
        }

        return $this->searchMovie($query, 'ru-RU');
    }

    /**
     * @return array<string, float|int|string|null>|null
     */
    public function findMovieById(int $tmdbId): ?array
    {
        $cacheKey = sprintf('cineclean.tmdb.movie.%d', $tmdbId);
        $ttl = now()->addDays((int) config('cineclean.tmdb.cache_days', 7));

        /** @var array<string, mixed>|null $movie */
        $movie = Cache::remember($cacheKey, $ttl, function () use ($tmdbId): ?array {
            $this->throttle();

            $response = $this->tmdbRequest()->get(
                sprintf('%s/movie/%d', rtrim((string) config('cineclean.tmdb.base_url'), '/'), $tmdbId),
                ['language' => 'en-US'],
            );

            if (! $response->successful()) {
                return null;
            }

            $payload = $response->json();

            if (! is_array($payload)) {
                return null;
            }

            return $this->normalizeMovie($payload);
        });

        return $movie;
    }

    /**
     * @return array<int, array<string, float|int|string|null>>
     */
    private function searchMovie(string $query, string $language): array
    {
        $cacheKey = sprintf('cineclean.tmdb.search.%s.%s', $language, sha1(mb_strtolower($query, 'UTF-8')));
        $ttl = now()->addDays((int) config('cineclean.tmdb.cache_days', 7));

        /** @var array<int, array<string, mixed>> $results */
        $results = Cache::remember($cacheKey, $ttl, function () use ($query, $language): array {
            $this->throttle();

            $response = $this->tmdbRequest()->get(
                sprintf('%s/search/movie', rtrim((string) config('cineclean.tmdb.base_url'), '/')),
                [
                    'query' => $query,
                    'language' => $language,
                ],
            );

            if (! $response->successful()) {
                return [];
            }

            $payload = $response->json('results');

            return is_array($payload) ? $payload : [];
        });

        return array_values(array_filter(array_map(fn (array $movie): ?array => $this->normalizeMovie($movie), $results)));
    }

    private function tmdbRequest(): PendingRequest
    {
        $request = Http::acceptJson()->timeout(20);
        $token = (string) config('cineclean.tmdb.token');

        if ($token !== '') {
            return $request->withToken($token);
        }

        $apiKey = (string) config('cineclean.tmdb.api_key');

        if ($apiKey !== '') {
            return $request->withQueryParameters(['api_key' => $apiKey]);
        }

        return $request;
    }

    private function throttle(): void
    {
        $windowSeconds = (int) config('cineclean.tmdb.window_seconds', 10);
        $maxRequests = (int) config('cineclean.tmdb.requests_per_window', 40);
        $now = microtime(true);

        $this->requestTimestamps = array_values(array_filter(
            $this->requestTimestamps,
            static fn (float $timestamp): bool => ($now - $timestamp) < $windowSeconds,
        ));

        if (count($this->requestTimestamps) >= $maxRequests) {
            $oldest = $this->requestTimestamps[0];
            $waitSeconds = $windowSeconds - ($now - $oldest);

            if ($waitSeconds > 0) {
                usleep((int) ($waitSeconds * 1_000_000));
            }

            $now = microtime(true);
            $this->requestTimestamps = array_values(array_filter(
                $this->requestTimestamps,
                static fn (float $timestamp): bool => ($now - $timestamp) < $windowSeconds,
            ));
        }

        $this->requestTimestamps[] = microtime(true);
    }

    /**
     * @param  array<string, mixed>  $movie
     * @return array<string, float|int|string|null>|null
     */
    private function normalizeMovie(array $movie): ?array
    {
        if (! isset($movie['id'])) {
            return null;
        }

        $releaseYear = null;
        $releaseDate = (string) ($movie['release_date'] ?? '');

        if ($releaseDate !== '' && preg_match('/^(\d{4})-\d{2}-\d{2}$/', $releaseDate, $matches)) {
            $releaseYear = (int) $matches[1];
        }

        $tmdbId = (int) $movie['id'];

        return [
            'tmdb_id' => $tmdbId,
            'title' => (string) ($movie['title'] ?? ''),
            'original_title' => (string) ($movie['original_title'] ?? ''),
            'release_year' => $releaseYear,
            'poster_path' => isset($movie['poster_path']) && is_string($movie['poster_path']) ? $movie['poster_path'] : null,
            'overview' => isset($movie['overview']) && is_string($movie['overview']) ? $movie['overview'] : null,
            'vote_average' => isset($movie['vote_average']) ? (float) $movie['vote_average'] : null,
            'tmdb_url' => sprintf('https://www.themoviedb.org/movie/%d', $tmdbId),
        ];
    }

    /**
     * @param  array<string, float|int|string|null>  $candidate
     */
    private function calculateConfidence(ParsedFilename $parsedFilename, array $candidate): float
    {
        $query = mb_strtolower($parsedFilename->cleanTitle, 'UTF-8');
        $candidateTitle = mb_strtolower((string) ($candidate['title'] ?? ''), 'UTF-8');
        $candidateOriginalTitle = mb_strtolower((string) ($candidate['original_title'] ?? ''), 'UTF-8');

        similar_text($query, $candidateTitle, $titleScore);
        similar_text($query, $candidateOriginalTitle, $originalTitleScore);

        $confidence = max($titleScore, $originalTitleScore) / 100;

        if ($parsedFilename->releaseYear !== null && isset($candidate['release_year']) && is_int($candidate['release_year'])) {
            if ($parsedFilename->releaseYear === $candidate['release_year']) {
                $confidence += 0.2;
            } elseif (abs($parsedFilename->releaseYear - $candidate['release_year']) === 1) {
                $confidence += 0.1;
            }
        }

        $voteAverage = isset($candidate['vote_average']) ? (float) $candidate['vote_average'] : 0.0;
        $confidence += min(0.1, max(0.0, $voteAverage / 100));

        return min(1.0, $confidence);
    }
}
