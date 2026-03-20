<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Throwable;

class McpTmdbService
{
    /**
     * @var array<int, string>|null
     */
    private ?array $cachedAvailableTools = null;

    public function isConfigured(): bool
    {
        return (bool) config('cineclean.tmdb.mcp_enabled', false)
            && trim((string) config('cineclean.tmdb.mcp_command', '')) !== '';
    }

    /**
     * @return array<int, array<string, float|int|string|null>>
     */
    public function searchMovies(string $query, string $language, ?int $year = null): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('TMDB MCP is not configured. Set TMDB_MCP_ENABLED and TMDB_MCP_COMMAND in .env.');
        }

        $toolName = $this->resolveSearchToolName();

        if ($toolName === null) {
            return [];
        }

        $result = $this->callMethod('tools/call', [
            'name' => $toolName,
            'arguments' => $this->buildSearchArguments($toolName, $query, $language, $year),
        ]);

        return $this->normalizeMovies($this->extractPayloadFromToolResult($result));
    }

    /**
     * @return array<string, float|int|string|null>|null
     */
    public function findMovieById(int $tmdbId, string $language): ?array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('TMDB MCP is not configured. Set TMDB_MCP_ENABLED and TMDB_MCP_COMMAND in .env.');
        }

        foreach ($this->buildMovieResourceUris($tmdbId, $language) as $uri) {
            try {
                $result = $this->callMethod('resources/read', ['uri' => $uri]);
                $movies = $this->normalizeMovies($this->extractPayloadFromResourceResult($result));

                if ($movies !== []) {
                    return $movies[0];
                }
            } catch (Throwable) {
            }
        }

        foreach ($this->resolveMovieToolNames() as $toolName) {
            try {
                $result = $this->callMethod('tools/call', [
                    'name' => $toolName,
                    'arguments' => $this->buildMovieArguments($tmdbId, $language),
                ]);
                $movies = $this->normalizeMovies($this->extractPayloadFromToolResult($result));

                if ($movies !== []) {
                    return $movies[0];
                }
            } catch (Throwable) {
            }
        }

        return null;
    }

    private function resolveSearchToolName(): ?string
    {
        $preferredToolNames = $this->configuredList(
            'cineclean.tmdb.mcp_search_tools',
            ['search_movies', 'search_movie', 'movie_search', 'find_movie'],
        );

        if ($preferredToolNames === []) {
            return null;
        }

        $availableTools = $this->discoverToolNames();

        if ($availableTools === []) {
            return $preferredToolNames[0];
        }

        foreach ($preferredToolNames as $preferredToolName) {
            if (in_array($preferredToolName, $availableTools, true)) {
                return $preferredToolName;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function resolveMovieToolNames(): array
    {
        $preferredToolNames = $this->configuredList(
            'cineclean.tmdb.mcp_movie_tools',
            ['get_movie_details', 'get_movie', 'movie_details'],
        );

        if ($preferredToolNames === []) {
            return [];
        }

        $availableTools = $this->discoverToolNames();

        if ($availableTools === []) {
            return $preferredToolNames;
        }

        return array_values(array_filter(
            $preferredToolNames,
            static fn (string $toolName): bool => in_array($toolName, $availableTools, true),
        ));
    }

    /**
     * @return array<int, string>
     */
    private function discoverToolNames(): array
    {
        if ($this->cachedAvailableTools !== null) {
            return $this->cachedAvailableTools;
        }

        $cacheKey = sprintf(
            'cineclean.tmdb.mcp.tools.%s',
            sha1((string) config('cineclean.tmdb.mcp_command', '')),
        );
        $cacheHours = max(1, (int) config('cineclean.tmdb.mcp_cache_hours', 12));

        /** @var array<int, string> $tools */
        $tools = Cache::remember($cacheKey, now()->addHours($cacheHours), function (): array {
            try {
                $result = $this->callMethod('tools/list', []);
            } catch (Throwable) {
                return [];
            }

            $items = $result['tools'] ?? null;

            if (! is_array($items)) {
                return [];
            }

            $toolNames = [];

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $name = trim((string) ($item['name'] ?? ''));

                if ($name === '') {
                    continue;
                }

                $toolNames[] = $name;
            }

            return array_values(array_unique($toolNames));
        });

        $this->cachedAvailableTools = $tools;

        return $this->cachedAvailableTools;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function callMethod(string $method, array $params): array
    {
        $payload = [
            [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'initialize',
                'params' => [
                    'protocolVersion' => '2024-11-05',
                    'capabilities' => (object) [],
                    'clientInfo' => [
                        'name' => 'moviesanalyzer',
                        'version' => '1.0.0',
                    ],
                ],
            ],
            [
                'jsonrpc' => '2.0',
                'method' => 'notifications/initialized',
                'params' => (object) [],
            ],
            [
                'jsonrpc' => '2.0',
                'id' => 2,
                'method' => $method,
                'params' => (object) $params,
            ],
        ];

        $responses = $this->runMcpSession($payload);

        foreach ($responses as $response) {
            $id = (string) ($response['id'] ?? '');

            if ($id !== '2') {
                continue;
            }

            if (isset($response['error']) && is_array($response['error'])) {
                $message = trim((string) ($response['error']['message'] ?? 'Unknown MCP error.'));
                throw new RuntimeException(sprintf('TMDB MCP call failed for %s: %s', $method, $message));
            }

            $result = $response['result'] ?? null;

            if (is_array($result)) {
                return $result;
            }

            throw new RuntimeException(sprintf('TMDB MCP call for %s returned invalid result payload.', $method));
        }

        throw new RuntimeException(sprintf('TMDB MCP call for %s returned no response payload.', $method));
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>>
     */
    private function runMcpSession(array $messages): array
    {
        $command = $this->prepareCommand(trim((string) config('cineclean.tmdb.mcp_command', '')));

        if ($command === '') {
            throw new RuntimeException('TMDB MCP command is empty.');
        }

        $timeout = max(5, (int) config('cineclean.tmdb.mcp_timeout_seconds', 30));
        $input = $this->buildInputPayload($messages);
        $environment = $this->processEnvironment($command);

        $result = Process::path(base_path())
            ->env($environment)
            ->timeout($timeout)
            ->input($input)
            ->run($command);

        if (! $result->successful()) {
            $stderr = trim($result->errorOutput());
            $suffix = $stderr !== '' ? sprintf(' STDERR: %s', $stderr) : '';
            throw new RuntimeException(sprintf('TMDB MCP command failed with exit code %d.%s', $result->exitCode(), $suffix));
        }

        $lines = preg_split('/\r\n|\r|\n/', $result->output()) ?: [];
        $decodedMessages = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);

            if (! is_array($decoded)) {
                continue;
            }

            $decodedMessages[] = $decoded;
        }

        return $decodedMessages;
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     */
    private function buildInputPayload(array $messages): string
    {
        $encoded = array_map(
            static fn (array $message): string => json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            $messages,
        );

        return implode("\n", $encoded)."\n";
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractPayloadFromToolResult(array $result): array|int|float|string|null
    {
        if (array_key_exists('structuredContent', $result)) {
            return $result['structuredContent'];
        }

        $content = $result['content'] ?? null;

        if (! is_array($content)) {
            return null;
        }

        $jsonPayloads = [];
        $textPayloads = [];

        foreach ($content as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            if (($entry['type'] ?? null) === 'json' && array_key_exists('json', $entry)) {
                $jsonPayloads[] = $entry['json'];
            }

            if (($entry['type'] ?? null) === 'text') {
                $text = trim((string) ($entry['text'] ?? ''));

                if ($text !== '') {
                    $textPayloads[] = $text;
                }
            }
        }

        if ($jsonPayloads !== []) {
            return count($jsonPayloads) === 1 ? $jsonPayloads[0] : $jsonPayloads;
        }

        if ($textPayloads === []) {
            return null;
        }

        $decoded = $this->decodeJsonPayload(implode("\n\n", $textPayloads));

        return $decoded ?? implode("\n\n", $textPayloads);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractPayloadFromResourceResult(array $result): array|int|float|string|null
    {
        $contents = $result['contents'] ?? null;

        if (! is_array($contents)) {
            return null;
        }

        foreach ($contents as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            if (array_key_exists('json', $entry)) {
                return $entry['json'];
            }

            $text = trim((string) ($entry['text'] ?? ''));

            if ($text === '') {
                continue;
            }

            $decoded = $this->decodeJsonPayload($text);

            if ($decoded !== null) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, float|int|string|null>>
     */
    private function normalizeMovies(array|int|float|string|null $payload): array
    {
        $items = $this->extractMovieItems($payload);
        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $movie = $this->normalizeMovie($item);

            if ($movie === null) {
                continue;
            }

            $normalized[$movie['tmdb_id']] = $movie;
        }

        return array_values($normalized);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractMovieItems(array|int|float|string|null $payload): array
    {
        if ($payload === null) {
            return [];
        }

        if (is_string($payload)) {
            $decoded = $this->decodeJsonPayload($payload);

            if ($decoded === null) {
                return [];
            }

            return $this->extractMovieItems($decoded);
        }

        if (! is_array($payload)) {
            return [];
        }

        if ($this->isMovieRow($payload)) {
            return [$payload];
        }

        $collectionKeys = ['results', 'movies', 'data', 'items', 'content'];

        foreach ($collectionKeys as $key) {
            $value = $payload[$key] ?? null;

            if (! is_array($value)) {
                continue;
            }

            if ($this->isList($value)) {
                return array_values(array_filter($value, 'is_array'));
            }

            if ($this->isMovieRow($value)) {
                return [$value];
            }
        }

        if ($this->isList($payload)) {
            return array_values(array_filter($payload, 'is_array'));
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $movie
     * @return array<string, float|int|string|null>|null
     */
    private function normalizeMovie(array $movie): ?array
    {
        $tmdbId = $this->extractInt($movie, ['tmdb_id', 'id', 'movie_id']);

        if ($tmdbId === null) {
            return null;
        }

        $mediaType = $this->extractString($movie, ['media_type']);

        if ($mediaType !== null && ! in_array(mb_strtolower($mediaType, 'UTF-8'), ['movie', 'tv'], true)) {
            return null;
        }

        if (
            ! array_key_exists('title', $movie)
            && ! array_key_exists('original_title', $movie)
            && array_key_exists('known_for_department', $movie)
        ) {
            return null;
        }

        $title = $this->extractString($movie, ['title', 'name', 'movie_title']) ?? '';
        $originalTitle = $this->extractString($movie, ['original_title', 'original_name', 'originalTitle']) ?? $title;

        if ($title === '') {
            return null;
        }

        $posterPath = $this->extractString($movie, ['poster_path', 'poster']);
        $overview = $this->extractString($movie, ['overview', 'description', 'plot']);
        $voteAverage = $this->extractFloat($movie, ['vote_average', 'rating']);
        $releaseYear = $this->extractInt($movie, ['release_year', 'year']);

        if ($releaseYear === null) {
            $releaseDate = $this->extractString($movie, ['release_date', 'first_air_date']);
            $releaseYear = $this->extractYearFromDate($releaseDate);
        }

        $tmdbUrl = $this->extractString($movie, ['tmdb_url', 'url']) ?? sprintf('https://www.themoviedb.org/movie/%d', $tmdbId);

        return [
            'tmdb_id' => $tmdbId,
            'title' => $title,
            'original_title' => $originalTitle,
            'release_year' => $releaseYear,
            'poster_path' => $posterPath,
            'overview' => $overview,
            'vote_average' => $voteAverage,
            'tmdb_url' => $tmdbUrl,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isMovieRow(array $row): bool
    {
        return array_key_exists('id', $row)
            || array_key_exists('tmdb_id', $row)
            || array_key_exists('title', $row)
            || array_key_exists('name', $row);
    }

    /**
     * @param  array<mixed>  $value
     */
    private function isList(array $value): bool
    {
        return array_is_list($value);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $keys
     */
    private function extractInt(array $payload, array $keys): ?int
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $payload)) {
                continue;
            }

            $value = $payload[$key];

            if (is_int($value)) {
                return $value;
            }

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $keys
     */
    private function extractFloat(array $payload, array $keys): ?float
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $payload)) {
                continue;
            }

            $value = $payload[$key];

            if (is_float($value) || is_int($value)) {
                return (float) $value;
            }

            if (is_string($value) && is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $keys
     */
    private function extractString(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $payload)) {
                continue;
            }

            $value = $payload[$key];

            if (! is_string($value)) {
                continue;
            }

            $normalized = trim($value);

            if ($normalized !== '') {
                return $normalized;
            }
        }

        return null;
    }

    private function extractYearFromDate(?string $date): ?int
    {
        if ($date === null || $date === '') {
            return null;
        }

        if (! preg_match('/\b(19\d{2}|20\d{2})\b/u', $date, $matches)) {
            return null;
        }

        $year = (int) $matches[1];

        if ($year < 1900 || $year > 2099) {
            return null;
        }

        return $year;
    }

    /**
     * @return array<string, mixed>|array<int, mixed>|int|float|string|null
     */
    private function decodeJsonPayload(string $value): array|int|float|string|null
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        $decoded = json_decode($trimmed, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        if (preg_match('/```(?:json)?\s*(.+?)```/isu', $trimmed, $matches) === 1) {
            $decoded = json_decode(trim((string) $matches[1]), true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        if (preg_match('/(\{.+\}|\[.+\])/su', $trimmed, $matches) === 1) {
            $decoded = json_decode((string) $matches[1], true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSearchArguments(string $toolName, string $query, string $language, ?int $year): array
    {
        $normalizedToolName = mb_strtolower(trim($toolName), 'UTF-8');

        if ($normalizedToolName === 'two_actors_on_screen') {
            return [
                'actor1' => $query,
                'actor2' => $query,
                'language' => $language,
            ];
        }

        if ($normalizedToolName === 'two_people') {
            return [
                'person1' => $query,
                'job1' => 'cast',
                'person2' => $query,
                'job2' => 'cast',
                'language' => $language,
            ];
        }

        if ($normalizedToolName === 'two_movies') {
            $arguments = [
                'movie1' => $query,
                'movie2' => $query,
                'language' => $language,
            ];

            if ($year !== null) {
                $arguments['year1'] = $year;
                $arguments['year2'] = $year;
            }

            return $arguments;
        }

        if (in_array($normalizedToolName, ['filmography_actor_genre', 'filmography_crew_genre'], true)) {
            return [
                'person' => $query,
                'language' => $language,
            ];
        }

        $arguments = [
            'query' => $query,
            'title' => $query,
            'movie' => $query,
            'movie1' => $query,
            'language' => $language,
        ];

        if ($year !== null) {
            $arguments['year'] = $year;
            $arguments['release_year'] = $year;
            $arguments['year1'] = $year;
        }

        return $arguments;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMovieArguments(int $tmdbId, string $language): array
    {
        return [
            'id' => $tmdbId,
            'movie_id' => $tmdbId,
            'tmdb_id' => $tmdbId,
            'language' => $language,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function buildMovieResourceUris(int $tmdbId, string $language): array
    {
        $templates = $this->configuredList(
            'cineclean.tmdb.mcp_resource_uris',
            ['tmdb://movie/{id}', 'tmdb:///movie/{id}', 'tmdb://movie/{id}?language={language}'],
        );

        return array_values(array_unique(array_map(
            static fn (string $template): string => strtr($template, [
                '{id}' => (string) $tmdbId,
                '{language}' => $language,
            ]),
            $templates,
        )));
    }

    /**
     * @param  array<int, string>  $default
     * @return array<int, string>
     */
    private function configuredList(string $configKey, array $default): array
    {
        $raw = trim((string) config($configKey, implode(',', $default)));

        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', $raw),
        )));
    }

    private function prepareCommand(string $command): string
    {
        if (! preg_match('/^\s*(?<runner>\S+)(?:\s+(?<rest>.*))?$/u', $command, $matches)) {
            return $command;
        }

        $runner = $this->stripWrappingQuotes((string) $matches['runner']);
        $rest = trim((string) ($matches['rest'] ?? ''));

        if ($runner === '') {
            return $command;
        }

        if (str_contains($runner, DIRECTORY_SEPARATOR)) {
            if (is_file($runner) && is_executable($runner)) {
                return $command;
            }

            throw new RuntimeException(sprintf(
                'TMDB MCP command failed: configured executable does not exist or is not executable (%s).',
                $runner,
            ));
        }

        $resolvedRunner = $this->resolveExecutable($runner);

        if ($resolvedRunner === null) {
            throw new RuntimeException($this->missingBinaryMessage($runner, $command));
        }

        if ($resolvedRunner === $runner) {
            return $command;
        }

        if ($rest === '') {
            return escapeshellarg($resolvedRunner);
        }

        return sprintf('%s %s', escapeshellarg($resolvedRunner), $rest);
    }

    private function stripWrappingQuotes(string $value): string
    {
        if (mb_strlen($value, 'UTF-8') < 2) {
            return $value;
        }

        $first = mb_substr($value, 0, 1, 'UTF-8');
        $last = mb_substr($value, -1, 1, 'UTF-8');

        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            return mb_substr($value, 1, mb_strlen($value, 'UTF-8') - 2, 'UTF-8');
        }

        return $value;
    }

    private function resolveExecutable(string $binary): ?string
    {
        $pathDirectories = array_filter(array_map(
            'trim',
            explode(PATH_SEPARATOR, (string) getenv('PATH')),
        ));

        $candidateDirectories = array_values(array_unique(array_merge(
            $pathDirectories,
            [
                '/opt/homebrew/bin',
                '/usr/local/bin',
                '/usr/bin',
                '/bin',
                '/opt/bin',
            ],
        )));

        foreach ($candidateDirectories as $directory) {
            $candidate = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$binary;

            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function missingBinaryMessage(string $binary, string $command): string
    {
        $path = (string) getenv('PATH');
        $baseMessage = sprintf(
            'TMDB MCP command failed: required binary "%s" was not found in PATH. Current command: %s.',
            $binary,
            $command,
        );

        if (in_array($binary, ['node', 'npm', 'npx', 'pnpm', 'tsx'], true)) {
            return sprintf(
                '%s Install Node.js 18+ and pnpm/tsx for the TMDB MCP server from https://github.com/leonardogilrodriguez/mcp-tmdb. PATH="%s".',
                $baseMessage,
                $path,
            );
        }

        return sprintf('%s PATH="%s".', $baseMessage, $path);
    }

    /**
     * @return array<string, string>
     */
    private function processEnvironment(string $command): array
    {
        $environment = [
            'PATH' => $this->buildProcessPath(),
        ];

        $home = trim((string) getenv('HOME'));

        if ($home !== '') {
            $environment['HOME'] = $home;
        }

        $tmdbApiKey = trim((string) config('cineclean.tmdb.api_key', ''));

        if ($tmdbApiKey !== '') {
            $environment['TMDB_API_KEY'] = $tmdbApiKey;
        }

        $tmdbToken = trim((string) config('cineclean.tmdb.token', ''));

        if ($tmdbToken !== '') {
            $environment['TMDB_TOKEN'] = $tmdbToken;
        }

        if (! $this->shouldSetUvEnvironment($command)) {
            return $environment;
        }

        $uvBaseDir = trim((string) config('cineclean.tmdb.mcp_uv_cache_dir', ''));

        if ($uvBaseDir === '') {
            return $environment;
        }

        $paths = [
            'UV_CACHE_DIR' => sprintf('%s/cache', rtrim($uvBaseDir, '/')),
            'UV_TOOL_DIR' => sprintf('%s/tools', rtrim($uvBaseDir, '/')),
            'UV_PYTHON_INSTALL_DIR' => sprintf('%s/python', rtrim($uvBaseDir, '/')),
            'XDG_CACHE_HOME' => sprintf('%s/xdg-cache', rtrim($uvBaseDir, '/')),
            'XDG_DATA_HOME' => sprintf('%s/xdg-data', rtrim($uvBaseDir, '/')),
        ];

        foreach ($paths as $key => $path) {
            if (! is_dir($path)) {
                @mkdir($path, 0755, true);
            }

            $environment[$key] = $path;
        }

        return $environment;
    }

    private function shouldSetUvEnvironment(string $command): bool
    {
        return preg_match('/(^|\s)(\S*uvx)(\s|$)/u', $command) === 1;
    }

    private function buildProcessPath(): string
    {
        $existingPath = array_filter(array_map(
            'trim',
            explode(PATH_SEPARATOR, (string) getenv('PATH')),
        ));

        $fallbackPath = [
            '/opt/homebrew/bin',
            '/usr/local/bin',
            '/usr/bin',
            '/bin',
            '/usr/sbin',
            '/sbin',
        ];

        $combined = array_values(array_unique(array_merge($existingPath, $fallbackPath)));

        return implode(PATH_SEPARATOR, $combined);
    }
}
