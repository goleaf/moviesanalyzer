<?php

namespace Database\Factories;

use App\Enums\MatchStatus;
use App\Models\MovieFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MovieFile>
 */
class MovieFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);
        $year = fake()->numberBetween(1970, 2025);

        return [
            'smb_path' => 'Movies/'.str_replace(' ', '.', $title).'.mkv',
            'filename' => str_replace(' ', '.', $title).'.mkv',
            'file_size_bytes' => fake()->numberBetween(600_000_000, 8_000_000_000),
            'extension' => 'mkv',
            'tmdb_id' => fake()->numberBetween(10, 999999),
            'tmdb_title' => ucwords($title),
            'tmdb_original_title' => ucwords($title),
            'tmdb_year' => $year,
            'movie_year' => $year,
            'tmdb_poster_path' => '/'.fake()->lexify('??????????????????????').'.jpg',
            'tmdb_overview' => fake()->sentence(10),
            'tmdb_vote_average' => fake()->randomFloat(1, 4, 9),
            'tmdb_url' => 'https://www.themoviedb.org/movie/'.fake()->numberBetween(10, 999999),
            'match_status' => MatchStatus::Matched,
            'match_confidence' => fake()->randomFloat(2, 0.7, 0.99),
            'scanned_at' => now(),
        ];
    }

    public function unmatched(): static
    {
        return $this->state(fn (): array => [
            'tmdb_id' => null,
            'tmdb_title' => null,
            'tmdb_original_title' => null,
            'tmdb_year' => null,
            'movie_year' => null,
            'tmdb_poster_path' => null,
            'tmdb_overview' => null,
            'tmdb_vote_average' => null,
            'tmdb_url' => null,
            'match_status' => MatchStatus::Unmatched,
            'match_confidence' => null,
        ]);
    }
}
