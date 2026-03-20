<?php

use App\Services\FilenameParser;
use App\Services\FilenameRuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('cleans movie filenames into canonical search titles', function (string $filename, string $expectedTitle, ?int $expectedYear): void {
    $parsed = app(FilenameParser::class)->parse($filename);

    expect($parsed->cleanTitle)->toBe($expectedTitle)
        ->and($parsed->releaseYear)->toBe($expectedYear);
})->with([
    ['The.Matrix.1999.1080p.BluRay.x264.mkv', 'The Matrix', 1999],
    ['матрица.avi', 'матрица', null],
    ['Matrix, The (1999).mp4', 'The Matrix', 1999],
    ['matrix_remastered_HDRip_720p.mp4', 'Matrix', null],
    ['[YTS.MX] The Matrix 1999 BluRay.mkv', 'The Matrix', 1999],
    ['The.Matrix.Reloaded.2003.4K.WEBRip.mkv', 'The Matrix Reloaded', 2003],
    ['The.Ritual.Killer.2023.BDRip.1080p_от New-Team_JNS82.mp4', 'The Ritual Killer', 2023],
    ['Uncharted.2022.1080p.FLEX.mp4', 'Uncharted', 2022],
    ['Whitney.Houston.2022.AMZN.WEB-DL.720p.x264.seleZen.mp4', 'Whitney Houston', 2022],
    ['Директор Рождество (2021).1080p.AMZN.WEB-DL.DDP5.1.H.264.mp4', 'директор рождество', 2021],
    ['Umami.2022.WEB-DL.2160p.SDR.seleZen.mp4', 'Umami', 2022],
    ['Thor.Love.and.Thunder.2022.D.BDRip.1080p.seleZen.mp4', 'Thor Love And Thunder', 2022],
]);

it('adds transliterated cyrillic query variants', function (): void {
    $parsed = app(FilenameParser::class)->parse('матрица.avi');

    expect($parsed->searchQueries)
        ->toContain('матрица')
        ->toContain('matritsa');
});

it('parses filename-like search input without stripping non-video suffixes', function (): void {
    $parsed = app(FilenameParser::class)->parseSearchInput('The.Matrix.1999');

    expect($parsed->cleanTitle)->toBe('The Matrix')
        ->and($parsed->releaseYear)->toBe(1999);
});

it('cleans transliterated filename-like search input used on unmatched page', function (): void {
    $parsed = app(FilenameParser::class)->parseSearchInput(
        'Zhutkaya.semeyka.svadebnyiy.perepoloh.2023.Web-Dl.1080P.elektri4Ka.uniongang',
    );

    expect($parsed->cleanTitle)->toBe('Zhutkaya Semeyka Svadebnyiy Perepoloh')
        ->and($parsed->releaseYear)->toBe(2023)
        ->and(collect($parsed->searchQueries)->contains(
            fn (string $query): bool => preg_match('/\p{Cyrillic}/u', $query) === 1,
        ))->toBeTrue();
});

it('recovers parser rules when active-rules cache is stale and empty', function (): void {
    Cache::put(FilenameRuleService::CACHE_KEY, collect(), now()->addMinutes(30));

    $service = app(FilenameRuleService::class);

    expect($service->shouldRemoveToken('web'))->toBeTrue()
        ->and($service->shouldRemoveToken('1080p'))->toBeTrue();
});
