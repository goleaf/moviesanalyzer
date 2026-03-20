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

    public function movieCards(string $search = '', int $page = 1, int $perPage = 50): LengthAwarePaginator
    {
        $searchTerms = $this->buildSearchTerms($search);

        $files = $this->matchedQuery()
            ->searchTerms($searchTerms)
            ->orderBy('tmdb_title')
            ->orderBy('movie_year')
            ->get();

        $groups = $this->buildGroups($files, duplicatesOnly: false)->values();

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
}
