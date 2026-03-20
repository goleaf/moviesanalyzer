<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use RuntimeException;

class McpFetchService
{
    public function isConfigured(): bool
    {
        return trim((string) config('cineclean.google_assist.mcp_command', '')) !== '';
    }

    public function fetch(string $url): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('MCP fetch is not configured. Set GOOGLE_ASSIST_MCP_COMMAND in .env.');
        }

        $command = $this->prepareCommand(trim((string) config('cineclean.google_assist.mcp_command', '')));
        $timeout = max(5, (int) config('cineclean.google_assist.mcp_timeout_seconds', 25));
        $input = $this->buildInputPayload($url);

        $result = Process::path(base_path())
            ->env($this->processEnvironment())
            ->timeout($timeout)
            ->input($input)
            ->run($command);

        if (! $result->successful()) {
            $stderr = trim($result->errorOutput());
            $suffix = $stderr !== '' ? sprintf(' STDERR: %s', $stderr) : '';
            throw new RuntimeException(sprintf('MCP fetch command failed with exit code %d.%s', $result->exitCode(), $suffix));
        }

        return $this->extractTextPayload($result->output());
    }

    private function buildInputPayload(string $url): string
    {
        $maxLength = max(1_000, (int) config('cineclean.google_assist.mcp_max_length', 18_000));
        $toolName = trim((string) config('cineclean.google_assist.mcp_tool', 'fetch')) ?: 'fetch';

        $messages = [
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
                'method' => 'tools/call',
                'params' => [
                    'name' => $toolName,
                    'arguments' => [
                        'url' => $url,
                        'max_length' => $maxLength,
                        'start_index' => 0,
                        'raw' => false,
                    ],
                ],
            ],
        ];

        $encoded = array_map(
            static fn (array $message): string => json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            $messages,
        );

        return implode("\n", $encoded)."\n";
    }

    private function extractTextPayload(string $output): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $output) ?: [];
        $toolResponse = null;
        $toolError = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);

            if (! is_array($decoded)) {
                continue;
            }

            if ((string) ($decoded['id'] ?? '') !== '2') {
                continue;
            }

            if (isset($decoded['error']) && is_array($decoded['error'])) {
                $toolError = trim((string) ($decoded['error']['message'] ?? 'Unknown MCP tool error.'));
            }

            if (isset($decoded['result']) && is_array($decoded['result'])) {
                $toolResponse = $decoded['result'];
            }
        }

        if ($toolResponse === null) {
            if ($toolError !== null && $toolError !== '') {
                throw new RuntimeException(sprintf('MCP tool call failed: %s', $toolError));
            }

            throw new RuntimeException('MCP fetch returned no tool response.');
        }

        $content = $toolResponse['content'] ?? null;
        $chunks = [];

        if (is_array($content)) {
            foreach ($content as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                if (($entry['type'] ?? null) !== 'text') {
                    continue;
                }

                $text = trim((string) ($entry['text'] ?? ''));

                if ($text !== '') {
                    $chunks[] = $text;
                }
            }
        }

        $textPayload = trim(implode("\n\n", $chunks));

        if ($textPayload !== '') {
            return $textPayload;
        }

        throw new RuntimeException('MCP fetch returned empty text payload.');
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
                'MCP fetch command failed: configured executable does not exist or is not executable (%s).',
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
            'MCP fetch command failed: required binary "%s" was not found in PATH. Current command: %s.',
            $binary,
            $command,
        );

        if ($binary === 'uvx') {
            return sprintf(
                '%s Install uv (`brew install uv` on macOS or `curl -LsSf https://astral.sh/uv/install.sh | sh` on Linux) and set GOOGLE_ASSIST_MCP_COMMAND to an absolute path, for example: "/opt/homebrew/bin/uvx mcp-server-fetch". PATH="%s".',
                $baseMessage,
                $path,
            );
        }

        return sprintf('%s PATH="%s".', $baseMessage, $path);
    }

    /**
     * @return array<string, string>
     */
    private function processEnvironment(): array
    {
        $environment = [
            'PATH' => $this->buildProcessPath(),
        ];
        $home = trim((string) getenv('HOME'));

        if ($home !== '') {
            $environment['HOME'] = $home;
        }

        $uvBaseDir = trim((string) config('cineclean.google_assist.mcp_uv_cache_dir', ''));

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
