<?php

use App\Services\FilenameParser;
use App\Services\FilenameRuleService;

it('cleans movie filenames into canonical search titles', function (string $filename, string $expectedTitle, ?int $expectedYear): void {
    $parsed = (new FilenameParser(new FilenameRuleService))->parse($filename);

    expect($parsed->cleanTitle)->toBe($expectedTitle)
        ->and($parsed->releaseYear)->toBe($expectedYear);
})->with([
    ['The.Matrix.1999.1080p.BluRay.x264.mkv', 'The Matrix', 1999],
    ['матрица.avi', 'матрица', null],
    ['Matrix, The (1999).mp4', 'The Matrix', null],
    ['matrix_remastered_HDRip_720p.mp4', 'Matrix', null],
    ['[YTS.MX] The Matrix 1999 BluRay.mkv', 'The Matrix', 1999],
    ['The.Matrix.Reloaded.2003.4K.WEBRip.mkv', 'The Matrix Reloaded', 2003],
    ['The.Ritual.Killer.2023.BDRip.1080p_от New-Team_JNS82.mp4', 'The Ritual Killer', 2023],
]);

it('adds transliterated cyrillic query variants', function (): void {
    $parsed = (new FilenameParser(new FilenameRuleService))->parse('матрица.avi');

    expect($parsed->searchQueries)
        ->toContain('матрица')
        ->toContain('matritsa');
});
