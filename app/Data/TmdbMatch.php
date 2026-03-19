<?php

namespace App\Data;

use App\Enums\MatchStatus;

class TmdbMatch
{
    public function __construct(
        public readonly MatchStatus $status,
        public readonly ?float $confidence = null,
        public readonly ?int $tmdbId = null,
        public readonly ?string $title = null,
        public readonly ?string $originalTitle = null,
        public readonly ?int $releaseYear = null,
        public readonly ?string $posterPath = null,
        public readonly ?string $overview = null,
        public readonly ?float $voteAverage = null,
        public readonly ?string $tmdbUrl = null,
    ) {}

    /**
     * @param  array<string, mixed>  $movie
     */
    public static function fromMovie(array $movie, MatchStatus $status, ?float $confidence): self
    {
        return new self(
            status: $status,
            confidence: $confidence,
            tmdbId: isset($movie['tmdb_id']) ? (int) $movie['tmdb_id'] : null,
            title: $movie['title'] ?? null,
            originalTitle: $movie['original_title'] ?? null,
            releaseYear: isset($movie['release_year']) ? (int) $movie['release_year'] : null,
            posterPath: $movie['poster_path'] ?? null,
            overview: $movie['overview'] ?? null,
            voteAverage: isset($movie['vote_average']) ? (float) $movie['vote_average'] : null,
            tmdbUrl: $movie['tmdb_url'] ?? null,
        );
    }

    public static function unmatched(): self
    {
        return new self(status: MatchStatus::Unmatched);
    }

    /**
     * @return array<string, float|int|string|null>
     */
    public function toDatabaseAttributes(): array
    {
        return [
            'tmdb_id' => $this->tmdbId,
            'tmdb_title' => $this->title,
            'tmdb_original_title' => $this->originalTitle,
            'tmdb_year' => $this->releaseYear,
            'movie_year' => $this->releaseYear,
            'tmdb_poster_path' => $this->posterPath,
            'tmdb_overview' => $this->overview,
            'tmdb_vote_average' => $this->voteAverage,
            'tmdb_url' => $this->tmdbUrl,
            'match_status' => $this->status,
            'match_confidence' => $this->confidence,
        ];
    }
}
