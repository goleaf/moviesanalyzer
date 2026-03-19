<?php

use App\Services\FilenameParser;
use App\Services\FilenameRuleService;

it('cleans movie filenames into canonical search titles', function (string $filename, string $expected): void {
    $parsed = (new FilenameParser(new FilenameRuleService))->parse($filename);

    expect($parsed->cleanTitle)->toBe($expected);
})->with([
    ['The.Matrix.1999.1080p.BluRay.x264.mkv', 'The Matrix'],
    ['матрица.avi', 'матрица'],
    ['Matrix, The (1999).mp4', 'The Matrix'],
    ['matrix_remastered_HDRip_720p.mp4', 'Matrix'],
    ['[YTS.MX] The Matrix 1999 BluRay.mkv', 'The Matrix'],
    ['The.Matrix.Reloaded.2003.4K.WEBRip.mkv', 'The Matrix Reloaded'],
]);

it('adds transliterated cyrillic query variants', function (): void {
    $parsed = (new FilenameParser(new FilenameRuleService))->parse('матрица.avi');

    expect($parsed->searchQueries)
        ->toContain('матрица')
        ->toContain('matritsa');
});
