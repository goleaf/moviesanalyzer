<?php

namespace App\Data;

class ParsedFilename
{
    /**
     * @param  array<int, string>  $searchQueries
     */
    public function __construct(
        public readonly string $originalFilename,
        public readonly string $baseName,
        public readonly string $cleanTitle,
        public readonly ?int $releaseYear,
        public readonly array $searchQueries,
    ) {
    }
}
