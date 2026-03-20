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
        public readonly ?string $originalLanguage = null,
        public readonly ?int $releaseYear = null,
        public readonly ?int $runtime = null,
        public readonly ?string $releaseDate = null,
        public readonly ?string $tagline = null,
        public readonly ?string $statusText = null,
        public readonly ?string $imdbId = null,
        public readonly ?string $posterPath = null,
        public readonly ?string $overview = null,
        public readonly ?float $voteAverage = null,
        public readonly ?float $popularity = null,
        public readonly ?int $voteCount = null,
        public readonly ?string $tmdbUrl = null,
        /** @var array<string, mixed>|null */
        public readonly ?array $tmdbMetadata = null,
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
            originalLanguage: $movie['tmdb_original_language'] ?? null,
            releaseYear: isset($movie['release_year']) ? (int) $movie['release_year'] : null,
            runtime: isset($movie['tmdb_runtime']) ? (int) $movie['tmdb_runtime'] : null,
            releaseDate: $movie['tmdb_release_date'] ?? null,
            tagline: $movie['tmdb_tagline'] ?? null,
            statusText: $movie['tmdb_status'] ?? null,
            imdbId: $movie['tmdb_imdb_id'] ?? null,
            posterPath: $movie['poster_path'] ?? null,
            overview: $movie['overview'] ?? null,
            voteAverage: isset($movie['vote_average']) ? (float) $movie['vote_average'] : null,
            popularity: isset($movie['tmdb_popularity']) ? (float) $movie['tmdb_popularity'] : null,
            voteCount: isset($movie['tmdb_vote_count']) ? (int) $movie['tmdb_vote_count'] : null,
            tmdbUrl: $movie['tmdb_url'] ?? null,
            tmdbMetadata: isset($movie['tmdb_metadata']) && is_array($movie['tmdb_metadata'])
                ? $movie['tmdb_metadata']
                : null,
        );
    }

    public static function unmatched(): self
    {
        return new self(status: MatchStatus::Unmatched);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabaseAttributes(): array
    {
        return [
            'tmdb_id' => $this->tmdbId,
            'tmdb_title' => $this->title,
            'tmdb_original_title' => $this->originalTitle,
            'tmdb_original_language' => $this->originalLanguage,
            'tmdb_year' => $this->releaseYear,
            'movie_year' => $this->releaseYear,
            'tmdb_runtime' => $this->runtime,
            'tmdb_release_date' => $this->releaseDate,
            'tmdb_tagline' => $this->tagline,
            'tmdb_status' => $this->statusText,
            'tmdb_imdb_id' => $this->imdbId,
            'tmdb_poster_path' => $this->posterPath,
            'tmdb_overview' => $this->overview,
            'tmdb_vote_average' => $this->voteAverage,
            'tmdb_popularity' => $this->popularity,
            'tmdb_vote_count' => $this->voteCount,
            'tmdb_url' => $this->tmdbUrl,
            'tmdb_metadata' => $this->tmdbMetadata,
            'match_status' => $this->status,
            'match_confidence' => $this->confidence,
        ];
    }
}
