<?php

namespace App\Livewire\Movies;

use App\Services\MovieLibraryService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class LibraryExplorerPanel extends Component
{
    use WithPagination;

    #[Url(as: 'q', keep: true)]
    public string $search = '';

    #[Url(as: 'sort', keep: true)]
    public string $sort = 'title';

    #[Url(as: 'dupes', keep: true)]
    public bool $duplicatesOnly = false;

    #[Url(as: 'view', keep: true)]
    public string $viewMode = 'grid';

    #[Url(as: 'perPage', keep: true)]
    public int $perPage = 24;

    /**
     * @var array<int, int>
     */
    public array $perPageOptions = [12, 24, 48, 96];

    /**
     * @var array<int, string>
     */
    private array $allowedSorts = ['title', 'year', 'rating', 'copies'];

    /**
     * @var array<int, string>
     */
    private array $allowedViewModes = ['grid', 'list'];

    public function mount(string $initialSearch = ''): void
    {
        if ($this->search === '' && trim($initialSearch) !== '') {
            $this->search = trim($initialSearch);
        }

        $this->search = trim($this->search);
        $this->sort = in_array($this->sort, $this->allowedSorts, true) ? $this->sort : 'title';
        $this->viewMode = in_array($this->viewMode, $this->allowedViewModes, true) ? $this->viewMode : 'grid';
        $this->perPage = in_array($this->perPage, $this->perPageOptions, true) ? $this->perPage : 24;
    }

    public function updatedSearch(): void
    {
        $this->search = trim($this->search);
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->sort = in_array($this->sort, $this->allowedSorts, true) ? $this->sort : 'title';
        $this->resetPage();
    }

    public function updatedDuplicatesOnly(): void
    {
        $this->duplicatesOnly = (bool) $this->duplicatesOnly;
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = in_array($this->perPage, $this->perPageOptions, true) ? $this->perPage : 24;
        $this->resetPage();
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = in_array($mode, $this->allowedViewModes, true) ? $mode : 'grid';
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->sort = 'title';
        $this->duplicatesOnly = false;
        $this->viewMode = 'grid';
        $this->perPage = 24;
        $this->resetPage();
    }

    #[Computed]
    public function movies(): LengthAwarePaginator
    {
        return app(MovieLibraryService::class)->movieCards(
            search: $this->search,
            page: max(1, $this->getPage()),
            perPage: $this->perPage,
            sort: $this->sort,
            duplicatesOnly: $this->duplicatesOnly,
        );
    }

    public function render(): View
    {
        return view('livewire.movies.library-explorer-panel');
    }
}
