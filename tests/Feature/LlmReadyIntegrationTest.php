<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('adds markdown discovery link header to html responses', function (): void {
    $response = $this->get(route('cineclean.dashboard'));
    $baseUrl = rtrim((string) config('app.url'), '/');

    $response->assertSuccessful()
        ->assertHeader('Link', "<{$baseUrl}/index.md>; rel=\"alternate\"; type=\"text/markdown\"");
});

it('serves markdown when requesting the dashboard with md extension', function (): void {
    $response = $this->get('/index.md');

    $response->assertSuccessful()
        ->assertHeader('X-LLM-Ready', 'true');

    $baseUrl = rtrim((string) config('app.url'), '/');

    expect((string) $response->headers->get('Content-Type'))->toContain('text/markdown');
    $response->assertSee('title:')
        ->assertSee("url: '{$baseUrl}/'", false);
});

it('serves llms txt endpoint with markdown content', function (): void {
    $response = $this->get('/llms.txt');

    $response->assertSuccessful()
        ->assertHeader('X-LLM-Ready', 'true');

    expect((string) $response->headers->get('Content-Type'))->toContain('text/markdown');
    $response->assertSee('## Core Pages')
        ->assertSee('Dashboard')
        ->assertSee('All Movies');
});

it('returns markdown 404 for excluded routes requested via md extension', function (): void {
    $response = $this->get('/stats.md');

    $response->assertNotFound()
        ->assertHeader('X-LLM-Ready', 'true');

    expect((string) $response->headers->get('Content-Type'))->toContain('text/markdown');
});
