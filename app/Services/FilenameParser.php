<?php

namespace App\Services;

use App\Data\ParsedFilename;

class FilenameParser
{
    /**
     * @var array<string, string>
     */
    private const CYRILLIC_MAP = [
        'а' => 'a',
        'б' => 'b',
        'в' => 'v',
        'г' => 'g',
        'д' => 'd',
        'е' => 'e',
        'ё' => 'yo',
        'ж' => 'zh',
        'з' => 'z',
        'и' => 'i',
        'й' => 'y',
        'к' => 'k',
        'л' => 'l',
        'м' => 'm',
        'н' => 'n',
        'о' => 'o',
        'п' => 'p',
        'р' => 'r',
        'с' => 's',
        'т' => 't',
        'у' => 'u',
        'ф' => 'f',
        'х' => 'kh',
        'ц' => 'ts',
        'ч' => 'ch',
        'ш' => 'sh',
        'щ' => 'shch',
        'ъ' => '',
        'ы' => 'y',
        'ь' => '',
        'э' => 'e',
        'ю' => 'yu',
        'я' => 'ya',
    ];

    /**
     * @var array<string, string>
     */
    private const LATIN_DIGRAPH_TO_CYRILLIC_MAP = [
        'shch' => 'щ',
        'yiy' => 'ый',
        'yy' => 'ый',
        'yo' => 'ё',
        'zh' => 'ж',
        'kh' => 'х',
        'ts' => 'ц',
        'ch' => 'ч',
        'sh' => 'ш',
        'yu' => 'ю',
        'ya' => 'я',
    ];

    /**
     * @var array<string, string>
     */
    private const LATIN_MAP = [
        'a' => 'а',
        'b' => 'б',
        'c' => 'к',
        'd' => 'д',
        'e' => 'е',
        'f' => 'ф',
        'g' => 'г',
        'h' => 'х',
        'i' => 'и',
        'j' => 'й',
        'k' => 'к',
        'l' => 'л',
        'm' => 'м',
        'n' => 'н',
        'o' => 'о',
        'p' => 'п',
        'q' => 'к',
        'r' => 'р',
        's' => 'с',
        't' => 'т',
        'u' => 'у',
        'v' => 'в',
        'w' => 'в',
        'x' => 'кс',
        'y' => 'й',
        'z' => 'з',
    ];

    public function __construct(private FilenameRuleService $filenameRuleService) {}

    public function parse(string $filename): ParsedFilename
    {
        $baseName = (string) pathinfo($filename, PATHINFO_FILENAME);

        return $this->parseFromBaseName($filename, $baseName);
    }

    public function parseSearchInput(string $input): ParsedFilename
    {
        $baseName = $this->searchBaseName($input);

        return $this->parseFromBaseName($input, $baseName);
    }

    private function parseFromBaseName(string $originalInput, string $baseName): ParsedFilename
    {
        $releaseYear = $this->extractReleaseYear($baseName);
        $normalized = $this->normalizeBaseName($baseName);
        $normalized = $this->filenameRuleService->applyReplacementRules($normalized);
        $normalized = $this->moveTrailingArticle($normalized);

        if ($releaseYear === null) {
            $releaseYear = $this->extractReleaseYear($normalized);
        }

        $tokens = preg_split('/\s+/u', $normalized) ?: [];
        $cleanTokens = [];
        $hasTechnicalTokens = false;

        foreach ($tokens as $token) {
            $token = trim($token, " \t\n\r\0\x0B.,");
            $normalizedToken = mb_strtolower(
                preg_replace('/[^\p{L}\p{N}]+/u', '', $token) ?? '',
                'UTF-8',
            );

            if ($normalizedToken === '') {
                continue;
            }

            if ($this->shouldTruncateParsing($normalizedToken, count($cleanTokens), $releaseYear, $hasTechnicalTokens)) {
                break;
            }

            if ($this->isYearToken($normalizedToken)) {
                continue;
            }

            if ($this->isDisposableToken($normalizedToken)) {
                $hasTechnicalTokens = true;

                continue;
            }

            $cleanTokens[] = $this->formatToken($token);
        }

        $cleanTitle = trim(preg_replace('/\s+/u', ' ', implode(' ', $cleanTokens)) ?? '');

        if ($cleanTitle === '') {
            $cleanTitle = trim($normalized);
        }

        $searchQueries = [$cleanTitle];

        if ($this->containsCyrillic($cleanTitle)) {
            $transliterated = $this->transliterate($cleanTitle);

            if ($transliterated !== '' && $transliterated !== $cleanTitle) {
                $searchQueries[] = $transliterated;
            }
        }

        if (! $this->containsCyrillic($cleanTitle) && $this->looksLikeTransliteratedLatin($cleanTitle)) {
            $cyrillic = $this->transliterateLatinToCyrillic($cleanTitle);

            if ($cyrillic !== '' && $cyrillic !== $cleanTitle) {
                $searchQueries[] = $cyrillic;
            }
        }

        return new ParsedFilename(
            originalFilename: $originalInput,
            baseName: $baseName,
            cleanTitle: $cleanTitle,
            releaseYear: $releaseYear,
            searchQueries: array_values(array_unique($searchQueries)),
        );
    }

    private function searchBaseName(string $input): string
    {
        $trimmedInput = trim($input);

        if ($trimmedInput === '') {
            return '';
        }

        $normalizedInput = str_replace('\\', '/', $trimmedInput);
        $baseName = (string) pathinfo($normalizedInput, PATHINFO_BASENAME);

        if ($baseName === '') {
            $baseName = $trimmedInput;
        }

        $extension = mb_strtolower((string) pathinfo($baseName, PATHINFO_EXTENSION), 'UTF-8');
        $videoExtensions = $this->videoExtensions();

        if ($extension !== '' && in_array($extension, $videoExtensions, true)) {
            return (string) pathinfo($baseName, PATHINFO_FILENAME);
        }

        return $baseName;
    }

    /**
     * @return array<int, string>
     */
    private function videoExtensions(): array
    {
        $extensions = config('cineclean.smb.video_extensions', []);

        if (! is_array($extensions)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $extension): string => mb_strtolower(trim((string) $extension), 'UTF-8'),
            $extensions,
        )));
    }

    private function normalizeBaseName(string $baseName): string
    {
        $normalized = preg_replace('/[._-]+/u', ' ', $baseName) ?? $baseName;

        return trim(preg_replace('/\s+/u', ' ', $normalized) ?? $normalized);
    }

    private function moveTrailingArticle(string $value): string
    {
        if (! preg_match('/^(?<main>.+),\s*(?<article>the|a|an)$/iu', $value, $matches)) {
            return $value;
        }

        return sprintf('%s %s', $matches['article'], $matches['main']);
    }

    private function extractReleaseYear(string $value): ?int
    {
        if (! preg_match('/\b(19\d{2}|20\d{2})\b/u', $value, $matches)) {
            return null;
        }

        $year = (int) $matches[1];

        if ($year < 1900 || $year > 2099) {
            return null;
        }

        return $year;
    }

    private function isYearToken(string $token): bool
    {
        if (! preg_match('/^(19\d{2}|20\d{2})$/', $token)) {
            return false;
        }

        $year = (int) $token;

        return $year >= 1900 && $year <= 2099;
    }

    private function isDisposableToken(string $token): bool
    {
        return $this->filenameRuleService->shouldRemoveToken($token);
    }

    private function formatToken(string $token): string
    {
        if ($this->containsCyrillic($token)) {
            return mb_strtolower($token, 'UTF-8');
        }

        return mb_convert_case(mb_strtolower($token, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    private function containsCyrillic(string $value): bool
    {
        return preg_match('/\p{Cyrillic}/u', $value) === 1;
    }

    private function transliterate(string $value): string
    {
        $normalized = mb_strtolower($value, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', strtr($normalized, self::CYRILLIC_MAP)) ?? '');
    }

    private function shouldTruncateParsing(
        string $normalizedToken,
        int $cleanTokenCount,
        ?int $releaseYear,
        bool $hasTechnicalTokens,
    ): bool {
        if ($cleanTokenCount < 2) {
            return false;
        }

        if ($this->filenameRuleService->shouldTruncateAfterToken($normalizedToken)) {
            return $releaseYear !== null || $hasTechnicalTokens;
        }

        if (! $hasTechnicalTokens) {
            return false;
        }

        return $this->looksLikeReleaseGroupToken($normalizedToken);
    }

    private function looksLikeReleaseGroupToken(string $token): bool
    {
        if (! preg_match('/^[a-z0-9]+$/u', $token)) {
            return false;
        }

        if (preg_match('/^(part|chapter|volume|vol|episode|season|movie)$/u', $token) === 1) {
            return false;
        }

        $length = mb_strlen($token, 'UTF-8');

        if ($length < 5) {
            return false;
        }

        if (preg_match('/\d/u', $token) === 1) {
            return true;
        }

        return $length >= 8;
    }

    private function looksLikeTransliteratedLatin(string $value): bool
    {
        if (preg_match('/\p{Cyrillic}/u', $value) === 1) {
            return false;
        }

        if (preg_match('/^[\p{Latin}\p{N}\s\'"]+$/u', $value) !== 1) {
            return false;
        }

        return preg_match('/(?:zh|kh|ch|sh|ya|yu|yo|yy|iy)/iu', $value) === 1;
    }

    private function transliterateLatinToCyrillic(string $value): string
    {
        $normalized = mb_strtolower($value, 'UTF-8');
        $normalized = strtr($normalized, self::LATIN_DIGRAPH_TO_CYRILLIC_MAP);
        $normalized = strtr($normalized, self::LATIN_MAP);

        return trim(preg_replace('/\s+/u', ' ', $normalized) ?? '');
    }
}
