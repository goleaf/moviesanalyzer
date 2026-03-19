<?php

namespace App\Services;

use App\Data\ParsedFilename;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GoogleMovieResearchService
{
    public function __construct(
        private FilenameParser $filenameParser,
        private TmdbService $tmdbService,
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
            'provider' => 'google_custom_search',
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
        return trim((string) config('cineclean.google_assist.api_key', '')) !== ''
            && trim((string) config('cineclean.google_assist.cx', '')) !== '';
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
            return [[], 'Google Assist is not configured. Set GOOGLE_CSE_API_KEY and GOOGLE_CSE_CX in .env.'];
        }

        $cacheKey = sprintf('moviesanalyzer.google_assist.%s', sha1(mb_strtolower($query, 'UTF-8')));
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
            $response = Http::acceptJson()
                ->timeout(20)
                ->retry(2, 250)
                ->get((string) config('cineclean.google_assist.endpoint', 'https://www.googleapis.com/customsearch/v1'), [
                    'key' => (string) config('cineclean.google_assist.api_key', ''),
                    'cx' => (string) config('cineclean.google_assist.cx', ''),
                    'q' => $query,
                    'num' => max(1, min(10, (int) config('cineclean.google_assist.max_results', 8))),
                ]);

            if (! $response->successful()) {
                return [
                    'results' => [],
                    'message' => sprintf('Google request failed with HTTP %d.', $response->status()),
                ];
            }

            $items = $response->json('items');

            if (! is_array($items)) {
                return [
                    'results' => [],
                    'message' => 'No Google results returned for this query.',
                ];
            }

            $results = [];

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $title = trim((string) ($item['title'] ?? ''));
                $snippet = trim((string) ($item['snippet'] ?? ''));
                $link = trim((string) ($item['link'] ?? ''));
                $displayLink = trim((string) ($item['displayLink'] ?? ''));

                $results[] = [
                    'title' => $title,
                    'snippet' => $snippet,
                    'link' => $link,
                    'display_link' => $displayLink,
                    'extracted_title' => $this->extractTitleFromText($title) ?? $this->extractTitleFromText($snippet),
                    'tmdb_id' => $this->extractTmdbId($link),
                ];
            }

            return [
                'results' => $results,
                'message' => null,
            ];
        });

        return [$payload['results'], $payload['message']];
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
