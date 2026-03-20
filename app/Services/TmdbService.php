<?php

namespace App\Services;

use App\Data\ParsedFilename;
use App\Data\TmdbMatch;
use App\Enums\MatchStatus;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class TmdbService
{
    private const FALLBACK_LANGUAGE = 'en-US';

    /**
     * @var array<int, float>
     */
    private array $requestTimestamps = [];

    private bool $mcpUnavailableForRuntime = false;

    public function __construct(
        private McpTmdbService $mcpTmdbService,
        private FilenameParser $filenameParser,
    ) {}

    public function match(ParsedFilename $parsedFilename): TmdbMatch
    {
        $this->assertProviderConfiguration();

        $candidates = collect();
        $preferredLanguage = $this->preferredLanguage();
        $searchYear = $this->normalizeSearchYear($parsedFilename->releaseYear);

        foreach ($parsedFilename->searchQueries as $query) {
            $primaryResults = $this->searchMovie($query, $preferredLanguage, $searchYear);

            if ($primaryResults === [] && $searchYear !== null) {
                $primaryResults = $this->searchMovie($query, $preferredLanguage, null);
            }

            $candidates = $candidates->merge($primaryResults);

            if ($primaryResults === [] && $preferredLanguage !== self::FALLBACK_LANGUAGE) {
                $fallbackResults = $this->searchMovie($query, self::FALLBACK_LANGUAGE, $searchYear);

                if ($fallbackResults === [] && $searchYear !== null) {
                    $fallbackResults = $this->searchMovie($query, self::FALLBACK_LANGUAGE, null);
                }

                $candidates = $candidates->merge($fallbackResults);
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
        $localizedBestCandidate = $this->localizeCandidate($bestCandidate, $preferredLanguage);

        if ($bestConfidence >= $matchThreshold) {
            return TmdbMatch::fromMovie($localizedBestCandidate, MatchStatus::Matched, $bestConfidence);
        }

        if ($bestConfidence >= $uncertainThreshold) {
            return TmdbMatch::fromMovie($localizedBestCandidate, MatchStatus::Uncertain, $bestConfidence);
        }

        return TmdbMatch::unmatched();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchCandidates(string $query, ?int $year = null): array
    {
        $this->assertProviderConfiguration();

        $preferredLanguage = $this->preferredLanguage();
        $parsedSearchInput = $this->filenameParser->parseSearchInput($query);
        $searchYear = $this->normalizeSearchYear($year ?? $parsedSearchInput->releaseYear);
        $searchQueries = $this->searchQueriesFromInput($query, $parsedSearchInput);
        $candidates = collect();

        foreach ($searchQueries as $searchQuery) {
            $primary = $this->searchMovie($searchQuery, $preferredLanguage, $searchYear);

            if ($primary === [] && $searchYear !== null) {
                $primary = $this->searchMovie($searchQuery, $preferredLanguage, null);
            }

            if ($primary === [] && $preferredLanguage !== self::FALLBACK_LANGUAGE) {
                $primary = $this->searchMovie($searchQuery, self::FALLBACK_LANGUAGE, $searchYear);

                if ($primary === [] && $searchYear !== null) {
                    $primary = $this->searchMovie($searchQuery, self::FALLBACK_LANGUAGE, null);
                }
            }

            $candidates = $candidates->merge($primary);
        }

        $uniqueCandidates = $candidates
            ->filter(fn (array $candidate): bool => isset($candidate['tmdb_id']))
            ->keyBy('tmdb_id')
            ->values()
            ->all();

        return $this->localizeCandidates($uniqueCandidates, $preferredLanguage);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findMovieById(int $tmdbId, ?string $language = null): ?array
    {
        $this->assertProviderConfiguration();

        $language = $language ?: $this->preferredLanguage();

        if ($this->shouldAttemptMcpProvider()) {
            try {
                $movie = $this->findMovieByIdViaMcp($tmdbId, $language);

                if ($movie !== null || ! $this->shouldUseHttpFallback()) {
                    return $movie;
                }
            } catch (Throwable $exception) {
                if (! $this->shouldUseHttpFallback()) {
                    throw $exception;
                }

                $this->disableMcpForRuntime($exception);
            }
        }

        if (! $this->shouldUseHttpProvider()) {
            return null;
        }

        return $this->findMovieByIdViaHttp($tmdbId, $language);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchMovie(string $query, string $language, ?int $year = null): array
    {
        if ($this->shouldAttemptMcpProvider()) {
            try {
                $movies = $this->searchMovieViaMcp($query, $language, $year);

                if ($movies !== [] || ! $this->shouldUseHttpFallback()) {
                    return $movies;
                }
            } catch (Throwable $exception) {
                if (! $this->shouldUseHttpFallback()) {
                    throw $exception;
                }

                $this->disableMcpForRuntime($exception);
            }
        }

        if (! $this->shouldUseHttpProvider()) {
            return [];
        }

        return $this->searchMovieViaHttp($query, $language, $year);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchMovieViaMcp(string $query, string $language, ?int $year = null): array
    {
        $searchYear = $this->normalizeSearchYear($year);
        $cacheKey = sprintf(
            'cineclean.tmdb.mcp.search.%s.%s.%s.%s',
            sha1((string) config('cineclean.tmdb.mcp_command', '')),
            $language,
            $searchYear ?? 'all-years',
            sha1(mb_strtolower($query, 'UTF-8')),
        );
        $ttl = now()->addDays((int) config('cineclean.tmdb.cache_days', 7));

        /** @var array<int, array<string, mixed>> $movies */
        $movies = Cache::remember($cacheKey, $ttl, function () use ($query, $language, $searchYear): array {
            return $this->mcpTmdbService->searchMovies($query, $language, $searchYear);
        });

        return $movies;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchMovieViaHttp(string $query, string $language, ?int $year = null): array
    {
        $searchYear = $this->normalizeSearchYear($year);
        $cacheKey = sprintf(
            'cineclean.tmdb.search.%s.%s.%s',
            $language,
            $searchYear ?? 'all-years',
            sha1(mb_strtolower($query, 'UTF-8')),
        );
        $ttl = now()->addDays((int) config('cineclean.tmdb.cache_days', 7));

        /** @var array<int, array<string, mixed>> $results */
        $results = Cache::remember($cacheKey, $ttl, function () use ($query, $language, $searchYear): array {
            $this->throttle();

            $params = [
                'query' => $query,
                'language' => $language,
            ];

            if ((bool) config('cineclean.tmdb.search_include_adult', false)) {
                $params['include_adult'] = true;
            }

            $region = trim((string) config('cineclean.tmdb.search_region', ''));

            if ($region !== '') {
                $params['region'] = $region;
            }

            if ($searchYear !== null) {
                $params['year'] = $searchYear;
            }

            $response = $this->tmdbRequest()->get(
                sprintf('%s/search/movie', rtrim((string) config('cineclean.tmdb.base_url'), '/')),
                $params,
            );

            if (! $response->successful()) {
                return [];
            }

            $payload = $response->json('results');

            return is_array($payload) ? $payload : [];
        });

        return array_values(array_filter(array_map(fn (array $movie): ?array => $this->normalizeMovie($movie), $results)));
    }

    private function normalizeSearchYear(?int $year): ?int
    {
        if ($year === null) {
            return null;
        }

        return $year >= 1900 && $year <= 2099 ? $year : null;
    }

    /**
     * @return array<int, string>
     */
    private function searchQueriesFromInput(string $query, ParsedFilename $parsedFilename): array
    {
        $queries = array_merge(
            [$query, $parsedFilename->cleanTitle, $parsedFilename->baseName],
            $parsedFilename->searchQueries,
        );

        $normalized = array_values(array_unique(array_filter(array_map(
            static fn (string $value): string => trim($value),
            $queries,
        ))));

        return array_slice($normalized, 0, 6);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findMovieByIdViaMcp(int $tmdbId, string $language): ?array
    {
        $cacheKey = sprintf(
            'cineclean.tmdb.mcp.movie.%s.%s.%d',
            sha1((string) config('cineclean.tmdb.mcp_command', '')),
            $language,
            $tmdbId,
        );
        $ttl = now()->addDays((int) config('cineclean.tmdb.cache_days', 7));

        /** @var array<string, mixed>|null $movie */
        $movie = Cache::remember($cacheKey, $ttl, function () use ($tmdbId, $language): ?array {
            return $this->mcpTmdbService->findMovieById($tmdbId, $language);
        });

        return $movie;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findMovieByIdViaHttp(int $tmdbId, string $language): ?array
    {
        $cacheKey = sprintf('cineclean.tmdb.movie.%s.%d', $language, $tmdbId);
        $ttl = now()->addDays((int) config('cineclean.tmdb.cache_days', 7));

        /** @var array<string, mixed>|null $movie */
        $movie = Cache::remember($cacheKey, $ttl, function () use ($tmdbId, $language): ?array {
            $this->throttle();
            $params = ['language' => $language];
            $appendToResponse = trim((string) config('cineclean.tmdb.details_append_to_response', ''));

            if ($appendToResponse !== '') {
                $params['append_to_response'] = $appendToResponse;
            }

            $includeImageLanguage = trim((string) config('cineclean.tmdb.details_include_image_language', ''));

            if ($includeImageLanguage !== '') {
                $params['include_image_language'] = $includeImageLanguage;
            }

            $response = $this->tmdbRequest()->get(
                sprintf('%s/movie/%d', rtrim((string) config('cineclean.tmdb.base_url'), '/'), $tmdbId),
                $params,
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

    private function assertProviderConfiguration(): void
    {
        if ($this->providerMode() === 'mcp_only' && ! $this->isMcpEnabled()) {
            throw new RuntimeException('TMDB MCP provider is set to mcp_only, but TMDB_MCP_ENABLED is false.');
        }
    }

    private function shouldAttemptMcpProvider(): bool
    {
        if ($this->mcpUnavailableForRuntime) {
            return false;
        }

        return $this->providerMode() !== 'http' && $this->isMcpEnabled();
    }

    private function shouldUseHttpProvider(): bool
    {
        return $this->providerMode() !== 'mcp_only';
    }

    private function shouldUseHttpFallback(): bool
    {
        return $this->shouldUseHttpProvider()
            && (bool) config('cineclean.tmdb.mcp_http_fallback', true);
    }

    private function providerMode(): string
    {
        $provider = mb_strtolower(trim((string) config('cineclean.tmdb.provider', 'http')), 'UTF-8');

        return in_array($provider, ['http', 'mcp_with_http_fallback', 'mcp_only'], true)
            ? $provider
            : 'http';
    }

    private function isMcpEnabled(): bool
    {
        return (bool) config('cineclean.tmdb.mcp_enabled', false);
    }

    private function disableMcpForRuntime(Throwable $exception): void
    {
        if ($this->mcpUnavailableForRuntime) {
            return;
        }

        $this->mcpUnavailableForRuntime = true;

        Log::warning('TMDB MCP provider failed, switching to HTTP provider for this request.', [
            'provider' => $this->providerMode(),
            'error' => $exception->getMessage(),
        ]);
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
     * @return array<string, mixed>|null
     */
    private function normalizeMovie(array $movie): ?array
    {
        if (! isset($movie['id'])) {
            return null;
        }

        $releaseDate = $this->normalizeReleaseDate(
            isset($movie['release_date']) && is_string($movie['release_date']) ? $movie['release_date'] : null,
        );
        $releaseYear = $this->extractReleaseYear($releaseDate);

        $tmdbId = (int) $movie['id'];
        $metadata = $this->buildMetadata($movie);

        return [
            'tmdb_id' => $tmdbId,
            'title' => (string) ($movie['title'] ?? ''),
            'original_title' => (string) ($movie['original_title'] ?? ''),
            'tmdb_original_language' => isset($movie['original_language']) && is_string($movie['original_language'])
                ? $movie['original_language']
                : null,
            'release_year' => $releaseYear,
            'tmdb_runtime' => isset($movie['runtime']) && is_numeric($movie['runtime']) ? (int) $movie['runtime'] : null,
            'tmdb_release_date' => $releaseDate,
            'tmdb_tagline' => isset($movie['tagline']) && is_string($movie['tagline']) ? $movie['tagline'] : null,
            'tmdb_status' => isset($movie['status']) && is_string($movie['status']) ? $movie['status'] : null,
            'tmdb_imdb_id' => isset($movie['imdb_id']) && is_string($movie['imdb_id']) ? $movie['imdb_id'] : null,
            'poster_path' => isset($movie['poster_path']) && is_string($movie['poster_path']) ? $movie['poster_path'] : null,
            'overview' => isset($movie['overview']) && is_string($movie['overview']) ? $movie['overview'] : null,
            'vote_average' => isset($movie['vote_average']) ? (float) $movie['vote_average'] : null,
            'tmdb_popularity' => isset($movie['popularity']) ? (float) $movie['popularity'] : null,
            'tmdb_vote_count' => isset($movie['vote_count']) && is_numeric($movie['vote_count'])
                ? (int) $movie['vote_count']
                : null,
            'tmdb_url' => sprintf('https://www.themoviedb.org/movie/%d', $tmdbId),
            'tmdb_metadata' => $metadata,
        ];
    }

    private function normalizeReleaseDate(?string $releaseDate): ?string
    {
        if ($releaseDate === null || $releaseDate === '') {
            return null;
        }

        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $releaseDate)) {
            return null;
        }

        return $releaseDate;
    }

    private function extractReleaseYear(?string $releaseDate): ?int
    {
        if ($releaseDate === null) {
            return null;
        }

        if (! preg_match('/^(\d{4})-\d{2}-\d{2}$/', $releaseDate, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * @param  array<string, mixed>  $movie
     * @return array<string, mixed>|null
     */
    private function buildMetadata(array $movie): ?array
    {
        $genres = $this->extractNamedList($movie['genres'] ?? null, 'name');
        $keywords = $this->extractKeywordNames($movie['keywords'] ?? null);
        $productionCountries = $this->extractNamedList($movie['production_countries'] ?? null, 'name');
        $spokenLanguages = $this->extractNamedList($movie['spoken_languages'] ?? null, 'english_name');
        $videos = $this->extractVideos($movie['videos'] ?? null);
        $watchProviders = $this->extractWatchProviders($movie['watch/providers'] ?? null);
        $releaseDates = $this->extractRegionalReleaseDates($movie['release_dates'] ?? null);
        $recommendations = $this->extractRelatedTitles($movie['recommendations'] ?? null);
        $similar = $this->extractRelatedTitles($movie['similar'] ?? null);
        $cast = $this->extractCastNames($movie['credits'] ?? null);
        $translations = $this->extractTranslationCodes($movie['translations'] ?? null);
        $alternativeTitles = $this->extractAlternativeTitles($movie['alternative_titles'] ?? null);

        $metadata = array_filter([
            'genres' => $genres,
            'keywords' => $keywords,
            'production_countries' => $productionCountries,
            'spoken_languages' => $spokenLanguages,
            'videos' => $videos,
            'watch_providers' => $watchProviders,
            'release_dates' => $releaseDates,
            'recommendations' => $recommendations,
            'similar' => $similar,
            'cast' => $cast,
            'translations' => $translations,
            'alternative_titles' => $alternativeTitles,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);

        return $metadata !== [] ? $metadata : null;
    }

    /**
     * @return array<int, string>|null
     */
    private function extractNamedList(mixed $value, string $nameKey): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $names = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = $item[$nameKey] ?? null;

            if (is_string($name) && trim($name) !== '') {
                $names[] = trim($name);
            }
        }

        if ($names === []) {
            return null;
        }

        return array_values(array_unique($names));
    }

    /**
     * @return array<int, string>|null
     */
    private function extractKeywordNames(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $keywordRows = $value['keywords'] ?? $value['results'] ?? null;

        if (! is_array($keywordRows)) {
            return null;
        }

        return $this->extractNamedList($keywordRows, 'name');
    }

    /**
     * @return array<int, string>|null
     */
    private function extractVideos(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $rows = $value['results'] ?? null;

        if (! is_array($rows)) {
            return null;
        }

        $videos = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $site = isset($row['site']) && is_string($row['site']) ? $row['site'] : null;
            $type = isset($row['type']) && is_string($row['type']) ? $row['type'] : null;
            $key = isset($row['key']) && is_string($row['key']) ? $row['key'] : null;

            if ($site === null || $type === null || $key === null) {
                continue;
            }

            $videos[] = sprintf('%s:%s:%s', $site, $type, $key);
        }

        if ($videos === []) {
            return null;
        }

        return array_slice($videos, 0, 20);
    }

    /**
     * @return array<int, string>|null
     */
    private function extractWatchProviders(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $results = $value['results'] ?? null;

        if (! is_array($results)) {
            return null;
        }

        $providers = [];

        foreach ($results as $regionData) {
            if (! is_array($regionData)) {
                continue;
            }

            foreach (['flatrate', 'rent', 'buy', 'ads', 'free'] as $bucket) {
                $bucketRows = $regionData[$bucket] ?? null;

                if (! is_array($bucketRows)) {
                    continue;
                }

                foreach ($bucketRows as $provider) {
                    if (! is_array($provider)) {
                        continue;
                    }

                    $name = $provider['provider_name'] ?? null;

                    if (is_string($name) && trim($name) !== '') {
                        $providers[] = trim($name);
                    }
                }
            }
        }

        if ($providers === []) {
            return null;
        }

        return array_slice(array_values(array_unique($providers)), 0, 50);
    }

    /**
     * @return array<int, string>|null
     */
    private function extractRegionalReleaseDates(mixed $value): ?array
    {
        if (! is_array($value) || ! is_array($value['results'] ?? null)) {
            return null;
        }

        $rows = [];

        foreach ($value['results'] as $resultRow) {
            if (! is_array($resultRow)) {
                continue;
            }

            $country = $resultRow['iso_3166_1'] ?? null;

            if (! is_string($country) || trim($country) === '') {
                continue;
            }

            if (is_array($resultRow['release_dates'] ?? null) && ($resultRow['release_dates'] !== [])) {
                $rows[] = trim($country);
            }
        }

        if ($rows === []) {
            return null;
        }

        return array_values(array_unique($rows));
    }

    /**
     * @return array<int, string>|null
     */
    private function extractRelatedTitles(mixed $value): ?array
    {
        if (! is_array($value) || ! is_array($value['results'] ?? null)) {
            return null;
        }

        $titles = [];

        foreach ($value['results'] as $resultRow) {
            if (! is_array($resultRow)) {
                continue;
            }

            $title = $resultRow['title'] ?? $resultRow['name'] ?? null;

            if (is_string($title) && trim($title) !== '') {
                $titles[] = trim($title);
            }
        }

        if ($titles === []) {
            return null;
        }

        return array_slice(array_values(array_unique($titles)), 0, 20);
    }

    /**
     * @return array<int, string>|null
     */
    private function extractCastNames(mixed $value): ?array
    {
        if (! is_array($value) || ! is_array($value['cast'] ?? null)) {
            return null;
        }

        return $this->extractNamedList(array_slice($value['cast'], 0, 20), 'name');
    }

    /**
     * @return array<int, string>|null
     */
    private function extractTranslationCodes(mixed $value): ?array
    {
        if (! is_array($value) || ! is_array($value['translations'] ?? null)) {
            return null;
        }

        $codes = [];

        foreach ($value['translations'] as $translation) {
            if (! is_array($translation)) {
                continue;
            }

            $iso639 = $translation['iso_639_1'] ?? null;
            $iso3166 = $translation['iso_3166_1'] ?? null;

            if (! is_string($iso639) || trim($iso639) === '') {
                continue;
            }

            if (is_string($iso3166) && trim($iso3166) !== '') {
                $codes[] = sprintf('%s-%s', trim($iso639), trim($iso3166));

                continue;
            }

            $codes[] = trim($iso639);
        }

        if ($codes === []) {
            return null;
        }

        return array_values(array_unique($codes));
    }

    /**
     * @return array<int, string>|null
     */
    private function extractAlternativeTitles(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $rows = $value['titles'] ?? $value['results'] ?? null;

        if (! is_array($rows)) {
            return null;
        }

        return $this->extractNamedList($rows, 'title');
    }

    /**
     * @param  array<string, mixed>  $candidate
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

    private function preferredLanguage(): string
    {
        return (string) config('cineclean.tmdb.preferred_language', 'ru-RU');
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @return array<string, mixed>
     */
    private function localizeCandidate(array $candidate, string $language): array
    {
        if (! isset($candidate['tmdb_id'])) {
            return $candidate;
        }

        $localized = $this->findMovieById((int) $candidate['tmdb_id'], $language);

        if ($localized === null) {
            return $candidate;
        }

        return $localized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $candidates
     * @return array<int, array<string, mixed>>
     */
    private function localizeCandidates(array $candidates, string $language): array
    {
        return array_values(array_map(
            fn (array $candidate): array => $this->localizeCandidate($candidate, $language),
            $candidates,
        ));
    }
}
