@extends('layouts.app')

@section('title', 'CineClean Duplicates')

@section('content')
    <section class="space-y-5">
        <div class="card p-4 md:p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-cinema text-3xl">Duplicate Groups</h2>
                <p class="text-muted text-sm mt-1">All removals are manual. Nothing is deleted until you confirm.</p>
            </div>

            <form method="GET" class="flex items-center gap-2">
                <label for="sort" class="text-xs uppercase tracking-wider text-muted">Sort by</label>
                <select id="sort" name="sort" onchange="this.form.submit()" class="bg-card border border-cine rounded-lg px-3 py-2 text-sm">
                    <option value="space" @selected($sort === 'space')>Space Wasted</option>
                    <option value="title" @selected($sort === 'title')>Title A-Z</option>
                    <option value="copies" @selected($sort === 'copies')>Most Copies</option>
                </select>
            </form>
        </div>

        @forelse ($groups as $group)
            <article class="card overflow-hidden {{ $group['uncertain'] ? 'border-l-4 border-l-amber-500' : '' }}">
                <div class="p-6 grid grid-cols-1 lg:grid-cols-[180px_1fr] gap-6">
                    <div>
                        @if ($group['poster_url'])
                            <img src="{{ $group['poster_url'] }}" alt="{{ $group['title'] }}" class="w-44 rounded-lg border border-cine shadow-xl">
                        @else
                            <div class="w-44 h-64 rounded-lg border border-cine bg-black/40 flex items-center justify-center text-xs text-muted">No poster</div>
                        @endif
                    </div>

                    <div class="space-y-4">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h3 class="font-cinema text-3xl leading-tight">{{ $group['title'] }}</h3>
                                <p class="text-sm text-muted mt-1">{{ $group['year'] ?: 'Year unknown' }} · ★ {{ $group['vote_average'] ? number_format($group['vote_average'], 1) : 'n/a' }}</p>
                                @if ($group['tmdb_url'])
                                    <a href="{{ $group['tmdb_url'] }}" target="_blank" rel="noopener" class="text-sm mt-2 inline-block text-amber-300 hover:text-amber-200">Open on TMDB</a>
                                @endif
                            </div>
                            <div class="text-right text-sm">
                                <p>{{ $group['copies'] }} copies · {{ \App\Models\MovieFile::formatBytes($group['total_size_bytes']) }} total</p>
                                <p class="text-amber-300">{{ \App\Models\MovieFile::formatBytes($group['wasted_size_bytes']) }} wasted</p>
                                @if ($group['uncertain'])
                                    <p class="mt-2 inline-flex text-xs px-2 py-1 rounded-full bg-amber-800/30 border border-amber-600/40">Low confidence match</p>
                                @endif
                            </div>
                        </div>

                        <p class="text-sm text-muted line-clamp-2">{{ $group['overview'] ?: 'No overview available.' }}</p>

                        <div class="overflow-x-auto border border-cine rounded-xl">
                            <table class="w-full text-left">
                                <thead class="bg-black/40 text-xs uppercase tracking-wider text-muted">
                                <tr>
                                    <th class="px-4 py-3">Filename</th>
                                    <th class="px-4 py-3">SMB Path</th>
                                    <th class="px-4 py-3">Size</th>
                                    <th class="px-4 py-3">Type</th>
                                    <th class="px-4 py-3 text-right">Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($group['files'] as $file)
                                    @include('partials.file-row', ['file' => $file])
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="card p-8 text-center">
                <p class="font-cinema text-2xl">No duplicates found yet.</p>
                <p class="text-muted mt-2">Run a scan and matched files will appear here.</p>
            </div>
        @endforelse
    </section>

    <div id="delete-modal" class="hidden fixed inset-0 z-50 bg-black/70 backdrop-blur-sm p-4">
        <div class="max-w-xl mx-auto mt-24 card p-6 space-y-5">
            <h3 class="font-cinema text-2xl">Delete from NAS?</h3>
            <p class="text-sm text-muted">This action is irreversible. The file will be removed from your SMB share.</p>
            <div class="rounded-xl border border-cine bg-black/30 p-4 text-sm space-y-1">
                <p id="modal-filename" class="font-mono break-all"></p>
                <p id="modal-size" class="text-muted"></p>
                <p id="modal-path" class="text-muted text-xs break-all"></p>
            </div>
            <div class="flex items-center justify-end gap-3">
                <button id="delete-cancel" type="button" class="px-4 py-2 rounded-lg border border-cine hover:bg-white/5">Cancel</button>
                <button id="delete-confirm" type="button" class="px-4 py-2 rounded-lg border border-red-500/50 bg-red-900/40 hover:bg-red-800/50">Confirm Delete</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('delete-modal');
            const filenameEl = document.getElementById('modal-filename');
            const sizeEl = document.getElementById('modal-size');
            const pathEl = document.getElementById('modal-path');
            const cancelButton = document.getElementById('delete-cancel');
            const confirmButton = document.getElementById('delete-confirm');
            let pendingTarget = null;

            document.querySelectorAll('.delete-trigger').forEach((button) => {
                button.addEventListener('click', () => {
                    pendingTarget = button;
                    filenameEl.textContent = button.dataset.filename || '';
                    sizeEl.textContent = `Size: ${button.dataset.size || 'n/a'}`;
                    pathEl.textContent = button.dataset.path || '';
                    modal.classList.remove('hidden');
                });
            });

            cancelButton.addEventListener('click', () => {
                modal.classList.add('hidden');
                pendingTarget = null;
            });

            confirmButton.addEventListener('click', async () => {
                if (!pendingTarget) {
                    return;
                }

                try {
                    const response = await fetch(pendingTarget.dataset.url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': window.CineClean.csrf,
                            'Accept': 'application/json',
                        },
                    });

                    const payload = await response.json();

                    if (!response.ok || payload.deleted !== true) {
                        throw new Error(payload.message || 'Delete failed.');
                    }

                    const row = document.querySelector(`[data-file-row="${pendingTarget.dataset.id}"]`);
                    if (row) {
                        row.style.transition = 'opacity .3s ease';
                        row.style.opacity = '0';
                        setTimeout(() => row.remove(), 300);
                    }

                    window.CineClean.toast('File deleted from SMB share.', 'success');
                    modal.classList.add('hidden');
                    pendingTarget = null;
                } catch (error) {
                    window.CineClean.toast(error.message || 'Delete failed.', 'error');
                }
            });
        })();
    </script>
@endpush
