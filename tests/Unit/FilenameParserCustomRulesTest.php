<?php

use App\Models\FilenameRule;
use App\Services\FilenameParser;
use App\Services\FilenameRuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('applies custom replacement and token removal rules without renaming files', function (): void {
    FilenameRule::query()->create([
        'rule_mode' => 'replace',
        'pattern' => 'Special\\s+Edition',
        'replacement' => '',
        'is_regex' => true,
        'is_case_sensitive' => false,
        'whole_word' => false,
        'sort_order' => 5,
        'is_active' => true,
        'notes' => 'remove edition text',
    ]);

    FilenameRule::query()->create([
        'rule_mode' => 'remove_token',
        'pattern' => 'fanedit',
        'replacement' => '',
        'is_regex' => false,
        'is_case_sensitive' => false,
        'whole_word' => true,
        'sort_order' => 6,
        'is_active' => true,
        'notes' => 'remove custom tag',
    ]);

    app(FilenameRuleService::class)->clearCache();

    $filename = 'Movie.Special Edition.FANEDIT.2022.mkv';
    $parsed = app(FilenameParser::class)->parse($filename);

    expect($parsed->originalFilename)->toBe($filename)
        ->and($parsed->cleanTitle)->toBe('Movie')
        ->and($parsed->releaseYear)->toBe(2022)
        ->and($parsed->searchQueries)->toContain('Movie');
});
