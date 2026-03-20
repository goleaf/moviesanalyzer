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
     * @var array<int, string>
     */
    private const REMOVABLE_TOKENS = [
        '4k',
        '2160p',
        '1080p',
        '720p',
        '480p',
        'hdrip',
        'bluray',
        'bdrip',
        'webrip',
        'webdl',
        'dvdrip',
        'camrip',
        'hdtv',
        'pdtv',
        'brrip',
        'x264',
        'x265',
        'hevc',
        'avc',
        'xvid',
        'divx',
        'h264',
        'h265',
        'ac3',
        'dts',
        'aac',
        'mp3',
        'truehd',
        'atmos',
        'remastered',
        'amzn',
        'sdr',
        'selezen',
        'd',
        'flex',
        'yts',
        'mx',
        'rarbg',
    ];

    public function __construct(private FilenameRuleService $filenameRuleService) {}

    public function parse(string $filename): ParsedFilename
    {
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
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

            if ($this->shouldStopAtReleaseSuffix($normalizedToken, count($cleanTokens), $releaseYear, $hasTechnicalTokens)) {
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

        return new ParsedFilename(
            originalFilename: $filename,
            baseName: $baseName,
            cleanTitle: $cleanTitle,
            releaseYear: $releaseYear,
            searchQueries: array_values(array_unique($searchQueries)),
        );
    }

    private function normalizeBaseName(string $baseName): string
    {
        $normalizedCodecs = preg_replace('/\bH[\s._-]?26([45])\b/iu', 'H26$1', $baseName) ?? $baseName;
        $normalizedAudio = preg_replace('/\bDDP?5[\s._-]?1\b/iu', 'DDP51', $normalizedCodecs) ?? $normalizedCodecs;
        $withoutBrackets = preg_replace('/\[[^\]]*]|\([^\)]*\)/u', ' ', $normalizedAudio) ?? $normalizedAudio;
        $withSpacing = preg_replace('/[._-]+/u', ' ', $withoutBrackets) ?? $withoutBrackets;

        return trim(preg_replace('/\s+/u', ' ', $withSpacing) ?? $withSpacing);
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
        if (in_array($token, self::REMOVABLE_TOKENS, true)) {
            return true;
        }

        if ($this->filenameRuleService->shouldRemoveToken($token)) {
            return true;
        }

        if (preg_match('/^\d{3,4}p$/', $token)) {
            return true;
        }

        if (preg_match('/^(web|dl)$/', $token)) {
            return true;
        }

        if (preg_match('/^dd5(?:1)?$/', $token)) {
            return true;
        }

        if (preg_match('/^ddp\d{1,2}$/', $token)) {
            return true;
        }

        if (preg_match('/^(s\d{1,2}e\d{1,2}|cd\d)$/', $token)) {
            return true;
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    public static function defaultRemovableTokens(): array
    {
        return self::REMOVABLE_TOKENS;
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

    private function shouldStopAtReleaseSuffix(
        string $normalizedToken,
        int $cleanTokenCount,
        ?int $releaseYear,
        bool $hasTechnicalTokens,
    ): bool {
        if ($cleanTokenCount < 2) {
            return false;
        }

        if ($normalizedToken !== 'от') {
            return false;
        }

        return $releaseYear !== null || $hasTechnicalTokens;
    }
}
