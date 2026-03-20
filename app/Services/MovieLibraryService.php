<?php

namespace App\Services;

use App\Enums\MatchStatus;
use App\Models\MovieFile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

class MovieLibraryService
{
    public function __construct(private FilenameParser $filenameParser) {}

    /**
     * @return array<string, int>
     */
    public function dashboardStats(): array
    {
        $files = MovieFile::query()
            ->select(['id', 'tmdb_id', 'file_size_bytes', 'match_status'])
            ->get();

        $groupedByTmdb = $files
            ->filter(fn (MovieFile $file): bool => $file->tmdb_id !== null)
            ->groupBy('tmdb_id');

        $duplicateGroups = $groupedByTmdb
            ->filter(fn (Collection $group): bool => $group->count() > 1);

        $spaceToReclaimBytes = (int) $duplicateGroups->sum(function (Collection $group): int {
            $totalSize = (int) $group->sum('file_size_bytes');
            $largestFile = (int) $group->max('file_size_bytes');

            return max(0, $totalSize - $largestFile);
        });

        return [
            'total_files' => $files->count(),
            'duplicate_groups' => $duplicateGroups->count(),
            'space_to_reclaim_bytes' => $spaceToReclaimBytes,
            'unmatched' => $files
                ->filter(fn (MovieFile $file): bool => in_array(
                    $file->match_status,
                    [MatchStatus::Unmatched, MatchStatus::Uncertain],
                    true,
                ))
                ->count(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function duplicateGroups(string $sort = 'space'): Collection
    {
        $files = $this->matchedQuery()->orderBy('tmdb_title')->get();

        $groups = $this->buildGroups($files, duplicatesOnly: true);

        return $this->sortGroups($groups, $sort);
    }

    public function movieCards(
        string $search = '',
        int $page = 1,
        int $perPage = 50,
        string $sort = 'title',
        bool $duplicatesOnly = false,
    ): LengthAwarePaginator {
        $searchTerms = $this->buildSearchTerms($search);

        $files = $this->matchedQuery()
            ->searchTerms($searchTerms)
            ->orderBy('tmdb_title')
            ->orderByDesc('movie_year')
            ->get();

        $groups = $this->buildGroups($files, duplicatesOnly: false)->values();

        if ($duplicatesOnly) {
            $groups = $groups
                ->filter(fn (array $group): bool => $group['copies'] > 1)
                ->values();
        }

        $groups = $this->sortMovieGroups($groups, $sort);

        $total = $groups->count();
        $slice = $groups
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        $paginator = new Paginator(
            items: $slice,
            total: $total,
            perPage: $perPage,
            currentPage: max($page, 1),
            options: ['path' => route('cineclean.movies.index')],
        );

        return $paginator->withQueryString();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function movieDetails(MovieFile $movieFile): ?array
    {
        if ($movieFile->tmdb_id === null) {
            return null;
        }

        $files = $this->matchedDetailsQuery()
            ->where('tmdb_id', $movieFile->tmdb_id)
            ->orderByDesc('file_size_bytes')
            ->orderBy('id')
            ->get();

        if ($files->isEmpty()) {
            return null;
        }

        $selectedFile = $files->firstWhere('id', $movieFile->id);
        /** @var MovieFile $primary */
        $primary = $selectedFile instanceof MovieFile ? $selectedFile : $files->first();
        $totalSizeBytes = (int) $files->sum('file_size_bytes');
        $metadata = is_array($primary->tmdb_metadata) ? $primary->tmdb_metadata : [];
        $localImages = data_get($metadata, 'local_images');

        if (! is_array($localImages)) {
            $localImages = [
                'posters' => [],
                'backdrops' => [],
                'logos' => [],
            ];
        } else {
            $localImages = array_replace([
                'posters' => [],
                'backdrops' => [],
                'logos' => [],
            ], $localImages);
        }

        $localPosters = is_array($localImages['posters'] ?? null) ? $localImages['posters'] : [];
        $localBackdrops = is_array($localImages['backdrops'] ?? null) ? $localImages['backdrops'] : [];
        $localLogos = is_array($localImages['logos'] ?? null) ? $localImages['logos'] : [];
        $fullPayload = is_array($metadata['full_payload'] ?? null) ? $metadata['full_payload'] : [];
        $genres = is_array($metadata['genres'] ?? null) ? $metadata['genres'] : [];
        $keywords = is_array($metadata['keywords'] ?? null) ? $metadata['keywords'] : [];
        $watchProviders = is_array($metadata['watch_providers'] ?? null) ? $metadata['watch_providers'] : [];
        $cast = is_array($metadata['cast'] ?? null) ? $metadata['cast'] : [];
        $budget = isset($fullPayload['budget']) && is_numeric($fullPayload['budget']) ? (int) $fullPayload['budget'] : null;
        $revenue = isset($fullPayload['revenue']) && is_numeric($fullPayload['revenue']) ? (int) $fullPayload['revenue'] : null;

        return [
            'tmdb_id' => $primary->tmdb_id,
            'title' => $primary->tmdb_title ?: $primary->tmdb_original_title,
            'original_title' => $primary->tmdb_original_title,
            'year' => $primary->movie_year ?? $primary->tmdb_year,
            'runtime' => $primary->tmdb_runtime,
            'release_date' => $primary->tmdb_release_date?->toDateString(),
            'tagline' => $primary->tmdb_tagline,
            'status' => $primary->tmdb_status,
            'imdb_id' => $primary->tmdb_imdb_id,
            'poster_url' => $primary->tmdb_poster_url,
            'overview' => $primary->tmdb_overview,
            'vote_average' => $primary->tmdb_vote_average,
            'vote_count' => $primary->tmdb_vote_count,
            'popularity' => $primary->tmdb_popularity,
            'tmdb_url' => $primary->tmdb_url,
            'metadata' => $metadata,
            'local_images' => $localImages,
            'local_posters' => $localPosters,
            'local_backdrops' => $localBackdrops,
            'local_logos' => $localLogos,
            'genres' => $genres,
            'keywords' => $keywords,
            'watch_providers' => $watchProviders,
            'cast' => $cast,
            'full_payload' => $fullPayload,
            'budget' => $budget,
            'revenue' => $revenue,
            'copies' => $files->count(),
            'files' => $files,
            'total_size_bytes' => $totalSizeBytes,
            'total_size_formatted' => MovieFile::formatBytes($totalSizeBytes),
            'primary_file_id' => $primary->id,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function buildSearchTerms(string $search): array
    {
        $normalizedSearch = trim($search);

        if ($normalizedSearch === '') {
            return [];
        }

        $parsedSearch = $this->filenameParser->parseSearchInput($normalizedSearch);
        $terms = array_merge(
            [$normalizedSearch, $parsedSearch->cleanTitle, $parsedSearch->baseName],
            $parsedSearch->searchQueries,
        );

        return array_slice(array_values(array_unique(array_filter(array_map(
            static fn (string $term): string => trim($term),
            $terms,
        )))), 0, 8);
    }

    private function matchedQuery(): Builder
    {
        return MovieFile::query()
            ->select([
                'id',
                'smb_path',
                'filename',
                'file_size_bytes',
                'extension',
                'tmdb_id',
                'tmdb_title',
                'tmdb_original_title',
                'tmdb_year',
                'movie_year',
                'tmdb_poster_path',
                'tmdb_overview',
                'tmdb_vote_average',
                'tmdb_url',
                'match_status',
                'match_confidence',
                'scanned_at',
                'created_at',
                'updated_at',
            ])
            ->matched()
            ->whereNotNull('tmdb_id');
    }

    private function matchedDetailsQuery(): Builder
    {
        return MovieFile::query()
            ->select([
                'id',
                'smb_path',
                'filename',
                'file_size_bytes',
                'extension',
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
                'created_at',
                'updated_at',
            ])
            ->matched()
            ->whereNotNull('tmdb_id');
    }

    /**
     * @param  Collection<int, MovieFile>  $files
     * @return Collection<int, array<string, mixed>>
     */
    private function buildGroups(Collection $files, bool $duplicatesOnly): Collection
    {
        $groups = $files
            ->groupBy('tmdb_id')
            ->map(function (Collection $group): array {
                $sortedFiles = $group->sortByDesc('file_size_bytes')->values();
                /** @var MovieFile $primary */
                $primary = $sortedFiles->first();
                $totalSize = (int) $sortedFiles->sum('file_size_bytes');
                $largest = (int) $sortedFiles->max('file_size_bytes');

                return [
                    'tmdb_id' => $primary->tmdb_id,
                    'primary_file_id' => $primary->id,
                    'title' => $primary->tmdb_title ?: $primary->tmdb_original_title,
                    'original_title' => $primary->tmdb_original_title,
                    'year' => $primary->movie_year ?? $primary->tmdb_year,
                    'poster_url' => $primary->tmdb_poster_url,
                    'overview' => $primary->tmdb_overview,
                    'vote_average' => $primary->tmdb_vote_average,
                    'tmdb_url' => $primary->tmdb_url,
                    'copies' => $sortedFiles->count(),
                    'total_size_bytes' => $totalSize,
                    'wasted_size_bytes' => max(0, $totalSize - $largest),
                    'uncertain' => $sortedFiles->contains(
                        fn (MovieFile $file): bool => $file->match_status === MatchStatus::Uncertain,
                    ),
                    'files' => $sortedFiles,
                ];
            })
            ->values();

        if (! $duplicatesOnly) {
            return $groups;
        }

        return $groups->filter(fn (array $group): bool => $group['copies'] > 1)->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $groups
     * @return Collection<int, array<string, mixed>>
     */
    private function sortGroups(Collection $groups, string $sort): Collection
    {
        return match ($sort) {
            'title' => $groups->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'copies' => $groups->sortByDesc('copies')->values(),
            default => $groups->sortByDesc('wasted_size_bytes')->values(),
        };
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $groups
     * @return Collection<int, array<string, mixed>>
     */
    private function sortMovieGroups(Collection $groups, string $sort): Collection
    {
        $normalizedSort = in_array($sort, ['title', 'year', 'rating', 'copies'], true)
            ? $sort
            : 'title';

        return match ($normalizedSort) {
            'year' => $groups->sort(function (array $a, array $b): int {
                $yearA = is_numeric($a['year'] ?? null) ? (int) $a['year'] : 0;
                $yearB = is_numeric($b['year'] ?? null) ? (int) $b['year'] : 0;

                if ($yearA !== $yearB) {
                    return $yearB <=> $yearA;
                }

                return strnatcasecmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
            })->values(),
            'rating' => $groups->sort(function (array $a, array $b): int {
                $ratingA = is_numeric($a['vote_average'] ?? null) ? (float) $a['vote_average'] : 0.0;
                $ratingB = is_numeric($b['vote_average'] ?? null) ? (float) $b['vote_average'] : 0.0;

                if ($ratingA !== $ratingB) {
                    return $ratingB <=> $ratingA;
                }

                return strnatcasecmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
            })->values(),
            'copies' => $groups->sortByDesc('copies')->values(),
            default => $groups->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE)->values(),
        };
    }
}
