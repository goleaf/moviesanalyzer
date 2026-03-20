<?php

namespace App\Services;

use App\Data\ParsedFilename;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GoogleMovieResearchService
{
    public function __construct(
        private FilenameParser $filenameParser,
        private TmdbService $tmdbService,
        private McpFetchService $mcpFetchService,
    ) {}

    /**
     * @return array{
     *     enabled: bool,
     *     provider: string,
     *     query: string,
     *     default_query: string,
     *     parsed_clean_title: string,
     *     title_suggestions: array<int, string>,
     *     google_results: array<int, array{
     *         title: string,
     *         snippet: string,
     *         link: string,
     *         display_link: string,
     *         extracted_title: string|null,
     *         tmdb_id: int|null
     *     }>,
     *     tmdb_candidates: array<int, array<string, float|int|string|null>>,
     *     message: string|null
     * }
     */
    public function research(string $filename, ?string $queryOverride = null): array
    {
        $parsedFilename = $this->filenameParser->parse($filename);
        $defaultQuery = $this->buildDefaultQuery($parsedFilename);
        $query = trim((string) ($queryOverride ?? '')) !== '' ? trim((string) $queryOverride) : $defaultQuery;

        [$googleResults, $message] = $this->searchGoogle($query);
        $titleSuggestions = $this->extractTitleSuggestions($googleResults, $parsedFilename);
        $tmdbCandidates = $this->buildTmdbCandidates($titleSuggestions, $googleResults);

        return [
            'enabled' => $this->isConfigured(),
            'provider' => (string) config('cineclean.google_assist.provider', 'mcp_google_fetch'),
            'query' => $query,
            'default_query' => $defaultQuery,
            'parsed_clean_title' => $parsedFilename->cleanTitle,
            'title_suggestions' => $titleSuggestions,
            'google_results' => $googleResults,
            'tmdb_candidates' => $tmdbCandidates,
            'message' => $message,
        ];
    }

    private function isConfigured(): bool
    {
        return $this->mcpFetchService->isConfigured();
    }

    private function buildDefaultQuery(ParsedFilename $parsedFilename): string
    {
        $baseQuery = trim($parsedFilename->cleanTitle) !== ''
            ? trim($parsedFilename->cleanTitle)
            : trim($parsedFilename->baseName);

        if ($parsedFilename->releaseYear !== null) {
            return trim(sprintf('%s %d movie', $baseQuery, $parsedFilename->releaseYear));
        }

        return trim(sprintf('%s movie', $baseQuery));
    }

    /**
     * @return array{0: array<int, array{
     *     title: string,
     *     snippet: string,
     *     link: string,
     *     display_link: string,
     *     extracted_title: string|null,
     *     tmdb_id: int|null
     * }>, 1: string|null}
     */
    private function searchGoogle(string $query): array
    {
        if (! $this->isConfigured()) {
            return [[], 'Google Assist MCP is not configured. Set GOOGLE_ASSIST_MCP_COMMAND in .env.'];
        }

        $cacheKey = sprintf(
            'moviesanalyzer.google_assist.%s.%s',
            sha1(mb_strtolower($query, 'UTF-8')),
            sha1((string) config('cineclean.google_assist.mcp_command', '')),
        );
        $cacheTtlDays = max(1, (int) config('cineclean.google_assist.cache_days', 2));

        /** @var array{results: array<int, array{
         *     title: string,
         *     snippet: string,
         *     link: string,
         *     display_link: string,
         *     extracted_title: string|null,
         *     tmdb_id: int|null
         * }, message: string|null} $payload
         */
        $payload = Cache::remember($cacheKey, now()->addDays($cacheTtlDays), function () use ($query): array {
            try {
                $document = $this->mcpFetchService->fetch($this->buildGoogleSearchUrl($query));
            } catch (Throwable $exception) {
                return [
                    'results' => [],
                    'message' => sprintf('Google MCP fetch failed: %s', $exception->getMessage()),
                ];
            }

            $results = $this->parseGoogleResults($document);

            if ($results === []) {
                return [
                    'results' => [],
                    'message' => 'No Google results returned for this query.',
                ];
            }

            return [
                'results' => $results,
                'message' => null,
            ];
        });

        return [$payload['results'], $payload['message']];
    }

    private function buildGoogleSearchUrl(string $query): string
    {
        $baseUrl = trim((string) config('cineclean.google_assist.google_search_url', 'https://www.google.com/search'));
        $baseUrl = $baseUrl !== '' ? $baseUrl : 'https://www.google.com/search';

        $params = [
            'q' => $query,
            'hl' => trim((string) config('cineclean.google_assist.google_locale', 'en')) ?: 'en',
            'safe' => trim((string) config('cineclean.google_assist.google_safe', 'off')) ?: 'off',
            'num' => max(1, min(10, (int) config('cineclean.google_assist.max_results', 8))),
            'gbv' => 1,
        ];

        return sprintf('%s?%s', rtrim($baseUrl, '?'), http_build_query($params, '', '&', PHP_QUERY_RFC3986));
    }

    /**
     * @return array<int, array{
     *     title: string,
     *     snippet: string,
     *     link: string,
     *     display_link: string,
     *     extracted_title: string|null,
     *     tmdb_id: int|null
     * }>
     */
    private function parseGoogleResults(string $document): array
    {
        $maxResults = max(1, min(10, (int) config('cineclean.google_assist.max_results', 8)));
        $results = [];
        $seenLinks = [];

        foreach ($this->extractGoogleLinks($document) as $match) {
            $normalizedUrl = $this->normalizeGoogleRedirectUrl($match['url']);

            if (! $this->isSearchCandidateUrl($normalizedUrl)) {
                continue;
            }

            $linkKey = mb_strtolower($normalizedUrl, 'UTF-8');

            if (isset($seenLinks[$linkKey])) {
                continue;
            }

            $seenLinks[$linkKey] = true;

            $title = $this->sanitizeText($match['title']);
            $snippet = $this->extractSnippet($document, $match['offset'], $title, $normalizedUrl);
            $displayLink = trim((string) parse_url($normalizedUrl, PHP_URL_HOST));

            $results[] = [
                'title' => $title !== '' ? $title : $displayLink,
                'snippet' => $snippet,
                'link' => $normalizedUrl,
                'display_link' => $displayLink,
                'extracted_title' => $this->extractTitleFromText($title) ?? $this->extractTitleFromText($snippet),
                'tmdb_id' => $this->extractTmdbId($normalizedUrl),
            ];

            if (count($results) >= $maxResults) {
                break;
            }
        }

        return $results;
    }

    /**
     * @return array<int, array{title: string, url: string, offset: int}>
     */
    private function extractGoogleLinks(string $document): array
    {
        $matches = [];

        if (preg_match_all('/\[(?<title>[^\]]+)\]\((?<url>https?:\/\/[^\s\)]+)\)/u', $document, $markdownLinks, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === false) {
            return [];
        }

        foreach ($markdownLinks as $entry) {
            $title = trim((string) ($entry['title'][0] ?? ''));
            $url = trim((string) ($entry['url'][0] ?? ''));
            $offset = (int) ($entry['url'][1] ?? 0);

            if ($url === '') {
                continue;
            }

            $matches[] = [
                'title' => $title,
                'url' => $url,
                'offset' => $offset,
            ];
        }

        if (preg_match_all('/https?:\/\/[^\s<>"\)]+/u', $document, $rawLinks, PREG_OFFSET_CAPTURE) === false) {
            return $matches;
        }

        foreach ($rawLinks[0] as $entry) {
            $url = trim((string) ($entry[0] ?? ''));
            $offset = (int) ($entry[1] ?? 0);

            if ($url === '') {
                continue;
            }

            $matches[] = [
                'title' => $url,
                'url' => $url,
                'offset' => $offset,
            ];
        }

        return $matches;
    }

    private function normalizeGoogleRedirectUrl(string $url): string
    {
        $normalized = trim($url);
        $host = trim((string) parse_url($normalized, PHP_URL_HOST));
        $path = trim((string) parse_url($normalized, PHP_URL_PATH));

        if ($host === '' || ! str_contains(mb_strtolower($host, 'UTF-8'), 'google.')) {
            return $normalized;
        }

        if (! in_array($path, ['/url', '/imgres'], true)) {
            return $normalized;
        }

        $query = trim((string) parse_url($normalized, PHP_URL_QUERY));

        if ($query === '') {
            return $normalized;
        }

        parse_str($query, $params);
        $redirect = trim((string) ($params['url'] ?? $params['q'] ?? ''));

        if ($redirect === '' || ! filter_var($redirect, FILTER_VALIDATE_URL)) {
            return $normalized;
        }

        return $redirect;
    }

    private function isSearchCandidateUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $host = mb_strtolower(trim((string) parse_url($url, PHP_URL_HOST)), 'UTF-8');

        if ($host === '') {
            return false;
        }

        $blockedHosts = [
            'google.com',
            'www.google.com',
            'support.google.com',
            'accounts.google.com',
            'policies.google.com',
            'webcache.googleusercontent.com',
        ];

        if (in_array($host, $blockedHosts, true)) {
            return false;
        }

        return ! str_starts_with($host, 'maps.google.');
    }

    private function extractSnippet(string $document, int $offset, string $title, string $url): string
    {
        $start = max(0, $offset - 140);
        $window = mb_substr($document, $start, 320, 'UTF-8');
        $window = preg_replace('/\[[^\]]+]\((https?:\/\/[^\s\)]+)\)/u', ' ', $window) ?? $window;
        $window = str_replace([$title, $url], ' ', $window);

        return mb_substr($this->sanitizeText($window), 0, 220, 'UTF-8');
    }

    private function sanitizeText(string $value): string
    {
        $cleaned = html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
        $cleaned = strip_tags($cleaned);
        $cleaned = trim(preg_replace('/\s+/u', ' ', $cleaned) ?? $cleaned);

        return trim($cleaned, " \t\n\r\0\x0B-_|:,.\"'[]()");
    }

    /**
     * @param  array<int, array{
     *     title: string,
     *     snippet: string,
     *     link: string,
     *     display_link: string,
     *     extracted_title: string|null,
     *     tmdb_id: int|null
     * }>  $googleResults
     * @return array<int, string>
     */
    private function extractTitleSuggestions(array $googleResults, ParsedFilename $parsedFilename): array
    {
        $scores = [];
        $this->addSuggestionScore($scores, $parsedFilename->cleanTitle, 4);

        foreach ($parsedFilename->searchQueries as $query) {
            $this->addSuggestionScore($scores, $query, 2);
        }

        foreach ($googleResults as $result) {
            $this->addSuggestionScore($scores, $result['extracted_title'], 3);
            $this->addSuggestionScore($scores, $this->extractTitleFromText($result['snippet']), 1);
        }

        uasort($scores, static fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        return array_values(array_map(
            static fn (array $entry): string => $entry['title'],
            array_slice($scores, 0, max(1, (int) config('cineclean.google_assist.max_title_suggestions', 6))),
        ));
    }

    /**
     * @param  array<string, array{title: string, score: int}>  $scores
     */
    private function addSuggestionScore(array &$scores, ?string $rawTitle, int $score): void
    {
        if ($rawTitle === null || trim($rawTitle) === '') {
            return;
        }

        $cleanedTitle = $this->filenameParser->parse($rawTitle.'.mkv')->cleanTitle;

        if ($cleanedTitle === '' || mb_strlen($cleanedTitle, 'UTF-8') < 2) {
            return;
        }

        $key = mb_strtolower($cleanedTitle, 'UTF-8');

        if (! isset($scores[$key])) {
            $scores[$key] = [
                'title' => $cleanedTitle,
                'score' => 0,
            ];
        }

        $scores[$key]['score'] += $score;
    }

    /**
     * @param  array<int, string>  $titleSuggestions
     * @param  array<int, array{
     *     title: string,
     *     snippet: string,
     *     link: string,
     *     display_link: string,
     *     extracted_title: string|null,
     *     tmdb_id: int|null
     * }>  $googleResults
     * @return array<int, array<string, float|int|string|null>>
     */
    private function buildTmdbCandidates(array $titleSuggestions, array $googleResults): array
    {
        $maxSuggestionQueries = max(1, (int) config('cineclean.google_assist.max_suggestion_queries', 3));
        $maxCandidates = max(1, (int) config('cineclean.google_assist.max_tmdb_candidates', 15));

        $candidates = [];

        foreach ($googleResults as $result) {
            if ($result['tmdb_id'] === null) {
                continue;
            }

            $movie = $this->tmdbService->findMovieById($result['tmdb_id']);

            if ($movie !== null) {
                $candidates[$movie['tmdb_id']] = $movie;
            }
        }

        foreach (array_slice($titleSuggestions, 0, $maxSuggestionQueries) as $title) {
            foreach ($this->tmdbService->searchCandidates($title) as $candidate) {
                if (! isset($candidate['tmdb_id'])) {
                    continue;
                }

                $tmdbId = (int) $candidate['tmdb_id'];

                if (! isset($candidates[$tmdbId])) {
                    $candidates[$tmdbId] = $candidate;
                }

                if (count($candidates) >= $maxCandidates) {
                    break 2;
                }
            }
        }

        return array_values($candidates);
    }

    private function extractTmdbId(string $url): ?int
    {
        if (! preg_match('#themoviedb\.org/movie/(\d+)#i', $url, $matches)) {
            return null;
        }

        $tmdbId = (int) $matches[1];

        return $tmdbId > 0 ? $tmdbId : null;
    }

    private function extractTitleFromText(string $text): ?string
    {
        $value = trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5));

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/\s*(?:\||-|:)\s*(?:IMDb|Wikipedia|Rotten Tomatoes|Official Trailer|Trailer|Review|The Movie Database).*$/iu', '', $value) ?? $value;
        $value = preg_replace('/\((?:19\d{2}|20\d{2})\)/u', ' ', $value) ?? $value;
        $value = trim($value, " \t\n\r\0\x0B-_|:,.\"'[]()");
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);

        if ($value === '') {
            return null;
        }

        return $value;
    }
}
