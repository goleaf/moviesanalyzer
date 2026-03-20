<section class="space-y-5">
    <div class="card p-5 md:p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-muted">Conflict Center</p>
                <h2 class="font-cinema mt-2 text-3xl">Duplicate Groups</h2>
                <p class="mt-2 text-sm text-muted">If MP4 exists, non-MP4 files are pre-selected. With multiple MP4 files, smaller MP4 files are also pre-selected.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                <label>
                    <span class="mb-1 block text-[11px] uppercase tracking-[0.16em] text-muted">Search</span>
                    <input
                        type="text"
                        wire:model.live.debounce.350ms="search"
                        placeholder="Title or TMDB ID..."
                        class="w-full rounded-xl border border-cine bg-slate-950/55 px-3 py-2 text-sm focus:border-amber-400/60 focus:outline-none"
                    >
                </label>
                <label>
                    <span class="mb-1 block text-[11px] uppercase tracking-[0.16em] text-muted">Sort</span>
                    <select wire:model.live="sort" class="w-full rounded-xl border border-cine bg-slate-950/55 px-3 py-2 text-sm focus:border-amber-400/60 focus:outline-none">
                        <option value="space">Space Wasted</option>
                        <option value="title">Title A-Z</option>
                        <option value="copies">Most Copies</option>
                    </select>
                </label>
                <div>
                    <span class="mb-1 block text-[11px] uppercase tracking-[0.16em] text-muted">Bulk cleanup</span>
                    @if ($deleteAllowed)
                        <button
                            type="button"
                            class="btn btn-danger w-full text-xs"
                            wire:click="startBulkDelete"
                            @disabled($this->selectedVisibleFileCount === 0 || $isBulkDeleting)
                        >
                            Delete Checked ({{ $this->selectedVisibleFileCount }})
                        </button>
                        <p class="mt-1 text-[11px] text-muted">Default keeps largest MP4 (if present), otherwise largest file.</p>
                    @else
                        <span class="inline-flex w-full items-center justify-center rounded-lg border border-slate-600/70 bg-slate-800/50 px-3 py-2 text-xs text-slate-400">
                            Delete disabled
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($isBulkDeleting)
        <div class="card border-amber-500/40 p-4" wire:poll.900ms="processBulkDeletion">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm font-semibold text-amber-200">Removing checked files one by one...</p>
                <p class="text-xs text-muted">{{ $this->bulkDeleteProgressPercent }}%</p>
            </div>
            <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-900/80">
                <div
                    class="h-full rounded-full bg-amber-400/90 transition-all duration-500"
                    style="width: {{ $this->bulkDeleteProgressPercent }}%;"
                ></div>
            </div>
            <p class="mt-2 text-xs text-muted">
                Processed {{ $bulkDeleteProcessed }} / {{ $bulkDeleteTotal }} · Removed {{ $bulkDeleteSucceeded }} · Failed {{ $bulkDeleteFailed }}
            </p>
            @if ($bulkDeleteCurrentFilename !== '')
                <p class="mt-1 break-all text-xs text-slate-300">Current: {{ $bulkDeleteCurrentFilename }}</p>
            @endif
        </div>
    @endif

    @forelse ($this->groups as $group)
        <article
            class="card overflow-hidden {{ $group['uncertain'] ? 'border-l-4 border-l-amber-500' : '' }}"
            data-duplicate-group="{{ $group['tmdb_id'] }}"
            wire:key="duplicate-group-{{ $group['tmdb_id'] }}"
        >
            <div class="grid grid-cols-1 gap-6 p-6 lg:grid-cols-[180px_1fr]">
                <div>
                    @if ($group['poster_url'])
                        <img src="{{ $group['poster_url'] }}" alt="{{ $group['title'] }}" class="w-44 rounded-xl border border-cine shadow-lg shadow-black/30">
                    @else
                        <div class="flex h-64 w-44 items-center justify-center rounded-xl border border-cine bg-slate-950/60 text-xs text-muted">No poster</div>
                    @endif
                </div>

                <div class="space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 class="font-cinema text-3xl leading-tight">{{ $group['title'] }}</h3>
                            <p class="mt-1 text-sm text-muted">
                                {{ $group['year'] ?: 'Year unknown' }} · ★ {{ $group['vote_average'] ? number_format($group['vote_average'], 1) : 'n/a' }}
                            </p>
                            @if ($group['tmdb_url'])
                                <a href="{{ $group['tmdb_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block text-sm text-amber-300 hover:text-amber-200">Open on TMDB</a>
                            @endif
                        </div>
                        <div class="text-right text-sm">
                            <p>{{ $group['copies'] }} copies · {{ \App\Models\MovieFile::formatBytes((int) $group['total_size_bytes']) }} total</p>
                            <p class="text-amber-300">{{ \App\Models\MovieFile::formatBytes((int) $group['wasted_size_bytes']) }} wasted</p>
                            @if ($group['uncertain'])
                                <p class="mt-2 inline-flex rounded-full border border-amber-600/40 bg-amber-900/30 px-2 py-1 text-xs">Low confidence match</p>
                            @endif
                        </div>
                    </div>

                    <p class="line-clamp-2 text-sm text-muted">{{ $group['overview'] ?: 'No overview available.' }}</p>

                    <div class="overflow-x-auto rounded-xl border border-cine">
                        <table class="w-full text-left">
                            <thead class="bg-slate-950/70 text-xs uppercase tracking-[0.16em] text-muted">
                            <tr>
                                <th class="px-4 py-3">Delete?</th>
                                <th class="px-4 py-3">Filename</th>
                                <th class="px-4 py-3">SMB Path</th>
                                <th class="px-4 py-3">Size</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($group['files'] as $file)
                                <tr class="border-t border-cine" wire:key="dup-file-{{ $file->id }}">
                                    <td class="px-4 py-3">
                                        @if ($deleteAllowed)
                                            <input
                                                type="checkbox"
                                                value="{{ $file->id }}"
                                                wire:model.live="selectedFileIds"
                                                class="h-4 w-4 rounded border-cine bg-slate-950/70 text-rose-500 focus:ring-rose-500/60"
                                                @disabled($isBulkDeleting)
                                            >
                                        @else
                                            <span class="text-xs text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="break-all px-4 py-3 font-mono text-xs md:text-sm">{{ $file->filename }}</td>
                                    <td class="break-all px-4 py-3 text-xs text-muted">{{ $file->smb_path }}</td>
                                    <td class="px-4 py-3 text-xs">{{ $file->formatted_size }}</td>
                                    <td class="px-4 py-3 text-xs uppercase">{{ $file->extension }}</td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($deleteAllowed)
                                            <button
                                                type="button"
                                                class="btn btn-danger text-xs"
                                                wire:click="deleteFile({{ $file->id }})"
                                                @disabled($isBulkDeleting)
                                            >
                                                Delete
                                            </button>
                                        @else
                                            <span class="inline-flex items-center rounded-lg border border-slate-600/70 bg-slate-800/50 px-3 py-1.5 text-xs text-slate-400">
                                                Delete disabled
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </article>
    @empty
        <div id="duplicates-empty-state" class="card p-8 text-center">
            <p class="font-cinema text-2xl">No duplicate conflicts for this filter.</p>
            <p class="mt-2 text-sm text-muted">Run another scan or clear your search query.</p>
        </div>
    @endforelse
</section>
