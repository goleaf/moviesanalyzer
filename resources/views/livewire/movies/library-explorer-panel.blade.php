<section class="space-y-6">
    <div class="card p-5 md:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-muted">Library Explorer</p>
                <h2 class="font-cinema mt-2 text-3xl">All Matched Movies</h2>
                <p class="mt-2 text-sm text-muted">
                    Live search, URL-synced filters, and fast pagination powered by Livewire 4.
                </p>
            </div>
            <button type="button" class="btn btn-ghost text-xs" wire:click="clearFilters">
                Reset Filters
            </button>
        </div>

        <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <label class="xl:col-span-2">
                <span class="mb-1 block text-[11px] uppercase tracking-[0.16em] text-muted">Search</span>
                <input
                    type="text"
                    wire:model.live.debounce.350ms="search"
                    placeholder="Title, filename, year, IMDb ID..."
                    class="w-full rounded-xl border border-cine bg-slate-950/55 px-3 py-2 text-sm focus:border-amber-400/60 focus:outline-none"
                >
            </label>

            <label>
                <span class="mb-1 block text-[11px] uppercase tracking-[0.16em] text-muted">Sort</span>
                <select wire:model.live="sort" class="w-full rounded-xl border border-cine bg-slate-950/55 px-3 py-2 text-sm focus:border-amber-400/60 focus:outline-none">
                    <option value="title">Title A-Z</option>
                    <option value="year">Year (Newest)</option>
                    <option value="rating">Rating (Highest)</option>
                    <option value="copies">Copies (Most)</option>
                </select>
            </label>

            <label>
                <span class="mb-1 block text-[11px] uppercase tracking-[0.16em] text-muted">Per Page</span>
                <select wire:model.live="perPage" class="w-full rounded-xl border border-cine bg-slate-950/55 px-3 py-2 text-sm focus:border-amber-400/60 focus:outline-none">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </label>

            <div class="space-y-2">
                <span class="block text-[11px] uppercase tracking-[0.16em] text-muted">View</span>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="btn flex-1 text-xs @if ($viewMode === 'grid') btn-primary @else btn-ghost @endif"
                        wire:click="setViewMode('grid')"
                    >
                        Grid
                    </button>
                    <button
                        type="button"
                        class="btn flex-1 text-xs @if ($viewMode === 'list') btn-primary @else btn-ghost @endif"
                        wire:click="setViewMode('list')"
                    >
                        List
                    </button>
                </div>
            </div>
        </div>

        <label class="mt-4 inline-flex items-center gap-2 text-sm text-slate-200">
            <input type="checkbox" wire:model.live="duplicatesOnly" class="h-4 w-4 rounded border-slate-500 bg-slate-900 text-amber-400 focus:ring-amber-500/50">
            Show only titles with duplicate copies
        </label>
    </div>

    @if ($this->movies->count() > 0)
        @if ($viewMode === 'grid')
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($this->movies as $group)
                    @include('partials.movie-card', ['group' => $group])
                @endforeach
            </div>
        @else
            <div class="card overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-cine bg-slate-950/70 text-xs uppercase tracking-[0.16em] text-muted">
                    <tr>
                        <th class="px-4 py-3">Movie</th>
                        <th class="px-4 py-3">Year</th>
                        <th class="px-4 py-3">Rating</th>
                        <th class="px-4 py-3">Copies</th>
                        <th class="px-4 py-3 text-right">Details</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($this->movies as $group)
                        <tr class="border-b border-cine/60">
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ $group['title'] }}</p>
                                <p class="text-xs text-muted">{{ $group['original_title'] }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $group['year'] ?? 'n/a' }}</td>
                            <td class="px-4 py-3">★ {{ isset($group['vote_average']) ? number_format((float) $group['vote_average'], 1) : 'n/a' }}</td>
                            <td class="px-4 py-3">{{ $group['copies'] }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('cineclean.movies.show', $group['primary_file_id']) }}" class="btn btn-ghost text-xs">Open</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="card p-4">
            {{ $this->movies->links() }}
        </div>
    @else
        <div class="card p-8 text-center">
            <p class="font-cinema text-2xl">No matched movies for this filter.</p>
            <p class="mt-2 text-sm text-muted">Try another query or disable the duplicate-only filter.</p>
        </div>
    @endif
</section>
