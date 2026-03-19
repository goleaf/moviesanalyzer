@extends('layouts.app')

@section('title', 'Unmatched Movies · CineClean')

@section('content')
    <section class="space-y-6">
        <div class="card p-4 md:p-6">
            <h2 class="font-cinema text-3xl">Unmatched Movies</h2>
            <p class="text-muted mt-1 text-sm">Search TMDB manually or skip uncertain files.</p>
        </div>

        <div class="card overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-black/40 text-xs uppercase tracking-wide text-muted">
                <tr>
                    <th class="px-4 py-3">Filename</th>
                    <th class="px-4 py-3">Full SMB Path</th>
                    <th class="px-4 py-3">Size</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($files as $file)
                    <tr class="border-t border-cine" data-unmatched-row="{{ $file->id }}">
                        <td class="px-4 py-3 font-mono text-xs md:text-sm break-all">{{ $file->filename }}</td>
                        <td class="px-4 py-3 text-xs text-muted break-all">{{ $file->smb_path }}</td>
                        <td class="px-4 py-3 text-xs">{{ $file->formatted_size }}</td>
                        <td class="px-4 py-3 text-xs uppercase">{{ $file->extension }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <button
                                    type="button"
                                    class="manual-search px-3 py-1.5 text-xs rounded-lg border border-cine bg-card hover:bg-white/5"
                                    data-id="{{ $file->id }}"
                                    data-search-url="{{ route('cineclean.unmatched.search', $file) }}"
                                    data-match-url="{{ route('cineclean.unmatched.match', $file) }}"
                                    data-filename="{{ $file->filename }}"
                                >
                                    Search TMDB manually
                                </button>
                                <button
                                    type="button"
                                    class="manual-skip px-3 py-1.5 text-xs rounded-lg border border-amber-500/40 bg-amber-900/20 hover:bg-amber-800/30"
                                    data-id="{{ $file->id }}"
                                    data-url="{{ route('cineclean.unmatched.skip', $file) }}"
                                >
                                    Skip
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-muted">No unmatched files.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card p-4">
            {{ $files->links() }}
        </div>
    </section>

    <div id="manual-modal" class="hidden fixed inset-0 z-50 bg-black/70 backdrop-blur-sm p-4">
        <div class="max-w-2xl mx-auto mt-20 card p-6 space-y-4">
            <h3 class="font-cinema text-2xl">Manual TMDB Search</h3>
            <input id="manual-query" type="text" class="w-full rounded-lg border border-cine bg-black/30 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
            <div class="flex items-center justify-between">
                <button id="manual-close" type="button" class="px-4 py-2 rounded-lg border border-cine hover:bg-white/5">Cancel</button>
                <button id="manual-search-submit" type="button" class="px-4 py-2 rounded-lg border border-cine bg-card hover:bg-white/5">Search</button>
            </div>
            <div id="manual-results" class="max-h-80 overflow-auto border border-cine rounded-xl p-3 text-sm space-y-2"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('manual-modal');
            const queryInput = document.getElementById('manual-query');
            const searchButton = document.getElementById('manual-search-submit');
            const closeButton = document.getElementById('manual-close');
            const resultsEl = document.getElementById('manual-results');
            let activeContext = null;

            const guessTitle = (filename) => filename
                .replace(/\.[^/.]+$/, '')
                .replace(/[._-]+/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();

            const hideModal = () => {
                modal.classList.add('hidden');
                activeContext = null;
                resultsEl.innerHTML = '';
            };

            const removeRow = (id) => {
                const row = document.querySelector(`[data-unmatched-row="${id}"]`);
                if (!row) {
                    return;
                }

                row.style.transition = 'opacity .3s ease';
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 300);
            };

            document.querySelectorAll('.manual-search').forEach((button) => {
                button.addEventListener('click', () => {
                    activeContext = {
                        id: button.dataset.id,
                        searchUrl: button.dataset.searchUrl,
                        matchUrl: button.dataset.matchUrl,
                    };

                    queryInput.value = guessTitle(button.dataset.filename || '');
                    resultsEl.innerHTML = '<p class="text-muted">Enter query and press Search.</p>';
                    modal.classList.remove('hidden');
                });
            });

            document.querySelectorAll('.manual-skip').forEach((button) => {
                button.addEventListener('click', async () => {
                    try {
                        const response = await fetch(button.dataset.url, {
                            method: 'PATCH',
                            headers: {
                                'X-CSRF-TOKEN': window.CineClean.csrf,
                                'Accept': 'application/json',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Skip failed.');
                        }

                        removeRow(button.dataset.id);
                        window.CineClean.toast('File marked as skipped.', 'info');
                    } catch (error) {
                        window.CineClean.toast(error.message || 'Skip failed.', 'error');
                    }
                });
            });

            closeButton.addEventListener('click', hideModal);

            searchButton.addEventListener('click', async () => {
                if (!activeContext) {
                    return;
                }

                resultsEl.innerHTML = '<p class="text-muted">Searching TMDB...</p>';

                try {
                    const response = await fetch(activeContext.searchUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': window.CineClean.csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ query: queryInput.value }),
                    });

                    const payload = await response.json();

                    if (!response.ok) {
                        throw new Error(payload.message || 'Search failed.');
                    }

                    if (!Array.isArray(payload.data) || payload.data.length === 0) {
                        resultsEl.innerHTML = '<p class="text-muted">No TMDB matches found.</p>';
                        return;
                    }

                    resultsEl.innerHTML = payload.data.map((movie) => {
                        return `
                            <div class="rounded-lg border border-cine p-3 flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold">${movie.title || 'Untitled'} (${movie.release_year || 'n/a'})</p>
                                    <p class="text-xs text-muted">★ ${movie.vote_average ?? 'n/a'} · TMDB #${movie.tmdb_id}</p>
                                </div>
                                <button
                                    class="pick-match px-3 py-1.5 rounded-lg border border-amber-500/40 bg-amber-900/20 hover:bg-amber-800/30 text-xs"
                                    data-tmdb-id="${movie.tmdb_id}"
                                >Use</button>
                            </div>
                        `;
                    }).join('');

                    resultsEl.querySelectorAll('.pick-match').forEach((pickButton) => {
                        pickButton.addEventListener('click', async () => {
                            try {
                                const matchResponse = await fetch(activeContext.matchUrl, {
                                    method: 'PATCH',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': window.CineClean.csrf,
                                        'Accept': 'application/json',
                                    },
                                    body: JSON.stringify({ tmdb_id: Number(pickButton.dataset.tmdbId) }),
                                });

                                if (!matchResponse.ok) {
                                    throw new Error('Applying match failed.');
                                }

                                removeRow(activeContext.id);
                                hideModal();
                                window.CineClean.toast('Manual match applied.', 'success');
                            } catch (error) {
                                window.CineClean.toast(error.message || 'Applying match failed.', 'error');
                            }
                        });
                    });
                } catch (error) {
                    resultsEl.innerHTML = '<p class="text-red-300">Search failed.</p>';
                    window.CineClean.toast(error.message || 'Search failed.', 'error');
                }
            });
        })();
    </script>
@endpush
