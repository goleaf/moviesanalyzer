<?php

namespace App\Livewire\Duplicates;

use App\Actions\DeleteMovieFilesAction;
use App\Models\MovieFile;
use App\Services\MovieLibraryService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class ConflictCenterPanel extends Component
{
    #[Url(as: 'sort', keep: true)]
    public string $sort = 'space';

    #[Url(as: 'q', keep: true)]
    public string $search = '';

    /**
     * @var array<int, int>
     */
    public array $selectedFileIds = [];

    /**
     * @var array<int, int>
     */
    public array $bulkDeleteQueue = [];

    public bool $deleteAllowed = false;

    public bool $isBulkDeleting = false;

    public int $bulkDeleteTotal = 0;

    public int $bulkDeleteProcessed = 0;

    public int $bulkDeleteSucceeded = 0;

    public int $bulkDeleteFailed = 0;

    public string $bulkDeleteCurrentFilename = '';

    /**
     * @var array<int, string>
     */
    private array $allowedSorts = ['space', 'title', 'copies'];

    public function mount(string $initialSort = 'space'): void
    {
        $this->sort = in_array($this->sort, $this->allowedSorts, true)
            ? $this->sort
            : (in_array($initialSort, $this->allowedSorts, true) ? $initialSort : 'space');
        $this->search = trim($this->search);
        $this->deleteAllowed = (bool) config('cineclean.files.allow_delete', false);
        $this->syncSelectedFilesWithVisibleGroups();
    }

    public function updatedSort(): void
    {
        $this->sort = in_array($this->sort, $this->allowedSorts, true) ? $this->sort : 'space';
        $this->syncSelectedFilesWithVisibleGroups();
    }

    public function updatedSearch(): void
    {
        $this->search = trim($this->search);
        $this->syncSelectedFilesWithVisibleGroups();
    }

    public function updatedSelectedFileIds(): void
    {
        $this->selectedFileIds = $this->normalizeIds($this->selectedFileIds);
    }

    public function deleteFile(int $movieFileId): void
    {
        if (! $this->deleteAllowed) {
            $this->dispatch('notify', message: 'File deletion is disabled.', type: 'error');

            return;
        }

        if ($this->isBulkDeleting) {
            $this->dispatch('notify', message: 'Bulk delete is running. Please wait until it completes.', type: 'info');

            return;
        }

        $deleteResult = $this->deleteMovieFile($movieFileId);

        if ($deleteResult['success']) {
            $this->dispatch('notify', message: 'File deleted from SMB share.', type: 'success');

            return;
        }

        $this->dispatch('notify', message: $deleteResult['message'], type: 'error');
    }

    public function startBulkDelete(): void
    {
        if (! $this->deleteAllowed) {
            $this->dispatch('notify', message: 'File deletion is disabled.', type: 'error');

            return;
        }

        if ($this->isBulkDeleting) {
            return;
        }

        $selectedVisibleFileIds = $this->selectedVisibleFileIds();

        if ($selectedVisibleFileIds === []) {
            $this->dispatch('notify', message: 'No files selected for bulk delete.', type: 'info');

            return;
        }

        $this->bulkDeleteQueue = $selectedVisibleFileIds;
        $this->bulkDeleteTotal = count($selectedVisibleFileIds);
        $this->bulkDeleteProcessed = 0;
        $this->bulkDeleteSucceeded = 0;
        $this->bulkDeleteFailed = 0;
        $this->bulkDeleteCurrentFilename = '';
        $this->isBulkDeleting = true;
    }

    public function processBulkDeletion(): void
    {
        if (! $this->isBulkDeleting) {
            return;
        }

        if ($this->bulkDeleteQueue === []) {
            $this->finishBulkDelete();

            return;
        }

        $nextFileId = (int) array_shift($this->bulkDeleteQueue);
        $deleteResult = $this->deleteMovieFile($nextFileId);

        $this->bulkDeleteProcessed++;
        $this->bulkDeleteCurrentFilename = $deleteResult['filename'];

        if ($deleteResult['success']) {
            $this->bulkDeleteSucceeded++;
        } else {
            $this->bulkDeleteFailed++;

            if ($deleteResult['message'] !== '') {
                $this->dispatch('notify', message: $deleteResult['message'], type: 'error');
            }
        }

        $this->selectedFileIds = $this->normalizeIds($this->bulkDeleteQueue);

        if ($this->bulkDeleteQueue === []) {
            $this->finishBulkDelete();
        }
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function groups(): Collection
    {
        return $this->filteredGroups();
    }

    #[Computed]
    public function selectedVisibleFileCount(): int
    {
        return count($this->selectedVisibleFileIds());
    }

    #[Computed]
    public function bulkDeleteProgressPercent(): int
    {
        if ($this->bulkDeleteTotal === 0) {
            return 0;
        }

        return (int) floor(($this->bulkDeleteProcessed / $this->bulkDeleteTotal) * 100);
    }

    public function render(): View
    {
        return view('livewire.duplicates.conflict-center-panel');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function filteredGroups(): Collection
    {
        $groups = app(MovieLibraryService::class)->duplicateGroups($this->sort);

        if ($this->search === '') {
            return $groups;
        }

        $needle = mb_strtolower($this->search, 'UTF-8');

        return $groups
            ->filter(function (array $group) use ($needle): bool {
                $title = mb_strtolower((string) ($group['title'] ?? ''), 'UTF-8');
                $originalTitle = mb_strtolower((string) ($group['original_title'] ?? ''), 'UTF-8');
                $tmdbId = (string) ($group['tmdb_id'] ?? '');

                return str_contains($title, $needle)
                    || str_contains($originalTitle, $needle)
                    || str_contains($tmdbId, $needle);
            })
            ->values();
    }

    private function syncSelectedFilesWithVisibleGroups(): void
    {
        $groups = $this->filteredGroups();
        $visibleFileIds = $this->visibleFileIds($groups);
        $defaultFileIds = $this->defaultFileIdsForGroups($groups);
        $selectedVisibleFileIds = array_values(array_intersect(
            $this->normalizeIds($this->selectedFileIds),
            $visibleFileIds,
        ));

        $this->selectedFileIds = $this->normalizeIds(array_merge($selectedVisibleFileIds, $defaultFileIds));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $groups
     * @return array<int, int>
     */
    private function defaultFileIdsForGroups(Collection $groups): array
    {
        return $groups
            ->flatMap(function (array $group): array {
                /** @var Collection<int, MovieFile> $files */
                $files = $group['files'];
                $hasMp4 = $files->contains(function (MovieFile $file): bool {
                    return strtolower((string) $file->extension) === 'mp4';
                });

                if ($hasMp4) {
                    $nonMp4Ids = $files
                        ->filter(function (MovieFile $file): bool {
                            return strtolower((string) $file->extension) !== 'mp4';
                        })
                        ->pluck('id')
                        ->map(fn (mixed $id): int => (int) $id)
                        ->all();

                    $mp4Files = $files
                        ->filter(function (MovieFile $file): bool {
                            return strtolower((string) $file->extension) === 'mp4';
                        })
                        ->sortByDesc('file_size_bytes')
                        ->values();

                    if ($mp4Files->count() <= 1) {
                        return $nonMp4Ids;
                    }

                    /** @var MovieFile|null $largestMp4File */
                    $largestMp4File = $mp4Files->first();

                    if (! $largestMp4File instanceof MovieFile) {
                        return $nonMp4Ids;
                    }

                    $smallerMp4Ids = $mp4Files
                        ->filter(fn (MovieFile $file): bool => $file->id !== $largestMp4File->id)
                        ->pluck('id')
                        ->map(fn (mixed $id): int => (int) $id)
                        ->all();

                    return array_values(array_unique(array_merge($nonMp4Ids, $smallerMp4Ids)));
                }

                /** @var MovieFile|null $largestFile */
                $largestFile = $files->sortByDesc('file_size_bytes')->first();

                if (! $largestFile instanceof MovieFile) {
                    return [];
                }

                return $files
                    ->filter(fn (MovieFile $file): bool => $file->id !== $largestFile->id)
                    ->pluck('id')
                    ->map(fn (mixed $id): int => (int) $id)
                    ->all();
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $groups
     * @return array<int, int>
     */
    private function visibleFileIds(Collection $groups): array
    {
        return $groups
            ->flatMap(function (array $group): array {
                /** @var Collection<int, MovieFile> $files */
                $files = $group['files'];

                return $files
                    ->pluck('id')
                    ->map(fn (mixed $id): int => (int) $id)
                    ->all();
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function selectedVisibleFileIds(): array
    {
        $visibleFileIds = $this->visibleFileIds($this->filteredGroups());

        return array_values(array_intersect(
            $this->normalizeIds($this->selectedFileIds),
            $visibleFileIds,
        ));
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return array<int, int>
     */
    private function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->filter(fn (mixed $id): bool => is_numeric($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{success: bool, filename: string, message: string}
     */
    private function deleteMovieFile(int $movieFileId): array
    {
        $payload = app(DeleteMovieFilesAction::class)->handle([$movieFileId]);
        $result = $payload['results'][0] ?? [
            'movie_file_id' => $movieFileId,
            'filename' => '',
            'success' => false,
            'message' => 'Movie file not found.',
        ];

        $this->selectedFileIds = array_values(array_diff(
            $this->normalizeIds($this->selectedFileIds),
            [$movieFileId],
        ));
        $this->bulkDeleteQueue = array_values(array_diff(
            $this->normalizeIds($this->bulkDeleteQueue),
            [$movieFileId],
        ));

        return [
            'success' => (bool) ($result['success'] ?? false),
            'filename' => is_string($result['filename'] ?? null) ? $result['filename'] : '',
            'message' => is_string($result['message'] ?? null) ? $result['message'] : 'Movie file not found.',
        ];
    }

    private function finishBulkDelete(): void
    {
        $this->isBulkDeleting = false;
        $this->bulkDeleteCurrentFilename = '';
        $this->bulkDeleteQueue = [];
        $this->syncSelectedFilesWithVisibleGroups();

        if ($this->bulkDeleteFailed > 0) {
            $this->dispatch(
                'notify',
                message: sprintf(
                    'Bulk delete finished: %d removed, %d failed.',
                    $this->bulkDeleteSucceeded,
                    $this->bulkDeleteFailed,
                ),
                type: 'info',
            );

            return;
        }

        $this->dispatch(
            'notify',
            message: sprintf('Bulk delete finished: %d files removed.', $this->bulkDeleteSucceeded),
            type: 'success',
        );
    }
}
