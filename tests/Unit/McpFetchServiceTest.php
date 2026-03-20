<?php

use App\Services\McpFetchService;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

uses(TestCase::class);

it('extracts text content from an mcp tool response payload', function (): void {
    config()->set('cineclean.google_assist.mcp_command', 'uvx mcp-server-fetch');

    Process::fake([
        '*' => Process::result(
            output: implode("\n", [
                json_encode([
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'result' => [
                        'protocolVersion' => '2024-11-05',
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                json_encode([
                    'jsonrpc' => '2.0',
                    'id' => 2,
                    'result' => [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'The Matrix (1999)',
                            ],
                        ],
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]),
        ),
    ]);

    $text = app(McpFetchService::class)->fetch('https://www.google.com/search?q=matrix');

    expect($text)->toContain('The Matrix (1999)');
});

it('throws when mcp command is not configured', function (): void {
    config()->set('cineclean.google_assist.mcp_command', '');

    expect(fn (): string => app(McpFetchService::class)->fetch('https://www.google.com/search?q=matrix'))
        ->toThrow(RuntimeException::class, 'GOOGLE_ASSIST_MCP_COMMAND');
});

it('throws when mcp tool response contains an error', function (): void {
    config()->set('cineclean.google_assist.mcp_command', 'uvx mcp-server-fetch');

    Process::fake([
        '*' => Process::result(
            output: implode("\n", [
                json_encode([
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'result' => [
                        'protocolVersion' => '2024-11-05',
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                json_encode([
                    'jsonrpc' => '2.0',
                    'id' => 2,
                    'error' => [
                        'code' => -32603,
                        'message' => 'Tool unavailable',
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]),
        ),
    ]);

    expect(fn (): string => app(McpFetchService::class)->fetch('https://www.google.com/search?q=matrix'))
        ->toThrow(RuntimeException::class, 'Tool unavailable');
});
