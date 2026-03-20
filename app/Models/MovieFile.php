<?php

namespace App\Models;

use App\Enums\MatchStatus;
use Database\Factories\MovieFileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovieFile extends Model
{
    /** @use HasFactory<MovieFileFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'smb_path',
        'filename',
        'file_size_bytes',
        'extension',
        'parsed_base_name',
        'parsed_clean_title',
        'parsed_search_queries',
        'parsed_release_year',
        'tmdb_id',
        'tmdb_title',
        'tmdb_original_title',
        'tmdb_original_language',
        'tmdb_year',
        'movie_year',
        'tmdb_runtime',
        'tmdb_release_date',
        'tmdb_tagline',
        'tmdb_status',
        'tmdb_imdb_id',
        'tmdb_poster_path',
        'tmdb_overview',
        'tmdb_vote_average',
        'tmdb_popularity',
        'tmdb_vote_count',
        'tmdb_url',
        'tmdb_metadata',
        'match_status',
        'match_confidence',
        'scanned_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'parsed_search_queries' => 'array',
            'parsed_release_year' => 'integer',
            'tmdb_id' => 'integer',
            'tmdb_year' => 'integer',
            'movie_year' => 'integer',
            'tmdb_runtime' => 'integer',
            'tmdb_release_date' => 'date',
            'tmdb_vote_average' => 'float',
            'tmdb_popularity' => 'float',
            'tmdb_vote_count' => 'integer',
            'tmdb_metadata' => 'array',
            'match_confidence' => 'float',
            'match_status' => MatchStatus::class,
            'scanned_at' => 'datetime',
        ];
    }

    protected function tmdbPosterUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->tmdb_poster_path) {
                return null;
            }

            if (str_starts_with($this->tmdb_poster_path, 'http')) {
                return $this->tmdb_poster_path;
            }

            return sprintf('https://image.tmdb.org/t/p/w200%s', $this->tmdb_poster_path);
        });
    }

    protected function formattedSize(): Attribute
    {
        return Attribute::get(fn (): string => self::formatBytes($this->file_size_bytes));
    }

    public function scopeMatched(Builder $query): Builder
    {
        return $query->whereIn('match_status', [
            MatchStatus::Matched->value,
            MatchStatus::Uncertain->value,
        ]);
    }

    public function scopeUnmatched(Builder $query): Builder
    {
        return $query->whereIn('match_status', [
            MatchStatus::Unmatched->value,
            MatchStatus::Uncertain->value,
        ]);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if ($search === null || $search === '') {
            return $query;
        }

        return $query->searchTerms([$search]);
    }

    /**
     * @param  array<int, string>  $terms
     */
    public function scopeSearchTerms(Builder $query, array $terms): Builder
    {
        $normalizedTerms = array_values(array_filter(array_map(
            static fn (string $term): string => trim($term),
            $terms,
        )));

        if ($normalizedTerms === []) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($normalizedTerms): void {
            foreach ($normalizedTerms as $term) {
                $builder->orWhere(function (Builder $termBuilder) use ($term): void {
                    $termBuilder
                        ->where('tmdb_title', 'like', "%{$term}%")
                        ->orWhere('tmdb_original_title', 'like', "%{$term}%")
                        ->orWhere('tmdb_imdb_id', 'like', "%{$term}%")
                        ->orWhere('parsed_clean_title', 'like', "%{$term}%")
                        ->orWhere('filename', 'like', "%{$term}%");

                    if (is_numeric($term)) {
                        $termBuilder
                            ->orWhere('movie_year', (int) $term)
                            ->orWhere('tmdb_year', (int) $term);
                    }
                });
            }
        });
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return sprintf('%d B', $bytes);
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;

        foreach ($units as $unit) {
            if ($value < 1024 || $unit === 'TB') {
                return sprintf('%.2f %s', $value, $unit);
            }

            $value /= 1024;
        }

        return sprintf('%.2f TB', $value);
    }
}
