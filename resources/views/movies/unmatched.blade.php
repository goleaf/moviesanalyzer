@extends('layouts.app')

@section('title', 'Unmatched Movies · moviesanalyzer')

@section('content')
    <section class="space-y-6">
        <div class="card p-4 md:p-6">
            <h2 class="font-cinema text-3xl">Unmatched Movies</h2>
            <p class="text-muted mt-1 text-sm">Search TMDB manually and confirm the correct match.</p>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <p id="unmatched-total" class="text-xs text-muted uppercase tracking-wide">Total unmatched: {{ $files->count() }}</p>
                <button
                    id="refresh-all-one-by-one"
                    type="button"
                    class="px-4 py-2 rounded-lg border border-amber-500/40 bg-amber-900/20 hover:bg-amber-800/30 text-xs md:text-sm"
                >
                    Refresh All One-by-One
                </button>
            </div>
            <p id="refresh-all-status" class="mt-2 text-xs text-muted"></p>
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
                        <td class="px-4 py-3">
                            <p class="font-mono text-xs md:text-sm break-all">{{ $file->filename }}</p>
                            <p class="text-[11px] text-muted mt-1">
                                Parsed:
                                <span class="text-primary">{{ $file->effective_clean_title }}</span>
                                @if ($file->effective_release_year)
                                    <span class="text-muted">({{ $file->effective_release_year }})</span>
                                @endif
                            </p>
                        </td>
                        <td class="px-4 py-3 text-xs text-muted break-all">{{ $file->smb_path }}</td>
                        <td class="px-4 py-3 text-xs">{{ $file->formatted_size }}</td>
                        <td class="px-4 py-3 text-xs uppercase">{{ $file->extension }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <button
                                    type="button"
                                    class="refresh-one px-3 py-1.5 text-xs rounded-lg border border-cine bg-card hover:bg-white/5"
                                    data-id="{{ $file->id }}"
                                    data-refresh-url="{{ route('cineclean.unmatched.refresh', $file) }}"
                                >
                                    Refresh
                                </button>
                                <button
                                    type="button"
                                    class="manual-search px-3 py-1.5 text-xs rounded-lg border border-cine bg-card hover:bg-white/5"
                                    data-id="{{ $file->id }}"
                                    data-search-url="{{ route('cineclean.unmatched.search', $file) }}"
                                    data-match-url="{{ route('cineclean.unmatched.match', $file) }}"
                                    data-filename="{{ $file->filename }}"
                                    data-clean-title="{{ $file->effective_clean_title }}"
                                    data-release-year="{{ $file->effective_release_year }}"
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

    </section>

    <div id="manual-modal" class="hidden fixed inset-0 z-50 bg-black/70 backdrop-blur-sm p-4">
        <div class="max-w-6xl w-full mx-auto mt-6 md:mt-10 card p-6 space-y-4">
            <h3 class="font-cinema text-2xl">Manual Research &amp; TMDB Search</h3>
            <div class="space-y-2">
                <label for="manual-query" class="text-xs uppercase tracking-wide text-muted">Movie Title</label>
                <input id="manual-query" type="text" class="w-full rounded-lg border border-cine bg-black/30 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
            </div>
            <div class="space-y-2">
                <label for="manual-year" class="text-xs uppercase tracking-wide text-muted">Movie Year (from filename)</label>
                <input id="manual-year" type="number" min="1900" max="2099" inputmode="numeric" class="w-full rounded-lg border border-cine bg-black/30 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
            </div>
            <div class="flex items-center justify-end gap-2">
                <button id="manual-close" type="button" class="px-4 py-2 rounded-lg border border-cine hover:bg-white/5">Cancel</button>
                <button id="manual-search-submit" type="button" class="px-4 py-2 rounded-lg border border-cine bg-card hover:bg-white/5">Search</button>
            </div>
            <div id="manual-results" class="max-h-[56vh] overflow-auto border border-cine rounded-xl p-3 text-sm space-y-3"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('manual-modal');
            const queryInput = document.getElementById('manual-query');
            const yearInput = document.getElementById('manual-year');
            const searchButton = document.getElementById('manual-search-submit');
            const closeButton = document.getElementById('manual-close');
            const resultsEl = document.getElementById('manual-results');
            const refreshAllButton = document.getElementById('refresh-all-one-by-one');
            const refreshAllStatus = document.getElementById('refresh-all-status');
            const unmatchedTotal = document.getElementById('unmatched-total');
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
                yearInput.value = '';
            };

            const removeRow = (id) => {
                const row = document.querySelector(`[data-unmatched-row="${id}"]`);
                if (!row) {
                    return;
                }

                row.style.transition = 'opacity .3s ease';
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    updateUnmatchedCounter();
                }, 300);
            };

            const updateUnmatchedCounter = () => {
                if (!unmatchedTotal) {
                    return;
                }

                const count = document.querySelectorAll('[data-unmatched-row]').length;
                unmatchedTotal.textContent = `Total unmatched: ${count}`;
            };

            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const safeUrl = (value) => {
                try {
                    const parsed = new URL(String(value ?? ''));
                    if (parsed.protocol === 'http:' || parsed.protocol === 'https:') {
                        return parsed.toString();
                    }
                } catch (error) {
                    return '#';
                }

                return '#';
            };

            const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

            const posterUrl = (movie) => {
                const raw = String(movie?.poster_path || '').trim();

                if (!raw) {
                    return '';
                }

                if (raw.startsWith('http://') || raw.startsWith('https://')) {
                    return safeUrl(raw);
                }

                return `https://image.tmdb.org/t/p/w200${raw}`;
            };

            const refreshMovie = async (button, quiet = false) => {
                const initialText = button.textContent;
                button.disabled = true;
                button.textContent = 'Refreshing...';

                try {
                    const response = await fetch(button.dataset.refreshUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': window.MoviesAnalyzer.csrf,
                            'Accept': 'application/json',
                        },
                    });

                    const payload = await response.json();

                    if (!response.ok) {
                        throw new Error(payload.message || payload.error || 'Refresh failed.');
                    }

                    if (payload.status === 'matched') {
                        removeRow(button.dataset.id);
                    }

                    if (!quiet) {
                        if (payload.status === 'matched') {
                            window.MoviesAnalyzer.toast('Movie matched and moved out of unmatched list.', 'success');
                        } else if (payload.status === 'failed') {
                            throw new Error(payload.error || 'Refresh failed.');
                        } else if (payload.status === 'uncertain') {
                            window.MoviesAnalyzer.toast('Movie refreshed with uncertain match.', 'info');
                        } else {
                            window.MoviesAnalyzer.toast('Movie refreshed but remains unmatched.', 'info');
                        }
                    }

                    return payload;
                } finally {
                    if (button.isConnected) {
                        button.disabled = false;
                        button.textContent = initialText;
                    }
                }
            };

            const openModal = (button) => {
                activeContext = {
                    id: button.dataset.id,
                    searchUrl: button.dataset.searchUrl,
                    matchUrl: button.dataset.matchUrl,
                    releaseYear: button.dataset.releaseYear ? Number(button.dataset.releaseYear) : null,
                };

                queryInput.value = button.dataset.cleanTitle || guessTitle(button.dataset.filename || '');
                yearInput.value = activeContext.releaseYear !== null ? String(activeContext.releaseYear) : '';
                resultsEl.innerHTML = '<p class="text-muted">Search TMDB manually.</p>';
                modal.classList.remove('hidden');
            };

            const renderTmdbResults = (movies) => {
                if (!Array.isArray(movies) || movies.length === 0) {
                    resultsEl.innerHTML = '<p class="text-muted">No TMDB matches found.</p>';
                    return;
                }

                resultsEl.innerHTML = movies.map((movie) => {
                    const overview = String(movie.overview || '').trim();
                    const poster = posterUrl(movie);

                    return `
                        <div class="rounded-xl border border-cine p-4 grid grid-cols-1 md:grid-cols-[120px,1fr,auto] gap-4 items-start">
                            <div class="w-[120px]">
                                ${poster ? `
                                    <img
                                        src="${escapeHtml(poster)}"
                                        alt="${escapeHtml(movie.title || 'Poster')}"
                                        loading="lazy"
                                        class="w-[120px] h-[172px] rounded-lg object-cover border border-cine/60"
                                    >
                                ` : `
                                    <div class="w-[120px] h-[172px] rounded-lg border border-cine/60 bg-black/40 text-muted text-xs flex items-center justify-center text-center px-2">
                                        No poster
                                    </div>
                                `}
                            </div>
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-base">${escapeHtml(movie.title || 'Untitled')} (${escapeHtml(movie.release_year || 'n/a')})</p>
                                    <a
                                        href="${safeUrl(movie.tmdb_url || '#')}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-xs text-amber-300 hover:text-amber-200 underline underline-offset-2"
                                    >
                                        TMDB
                                    </a>
                                </div>
                                <p class="text-xs text-muted">★ ${escapeHtml(movie.vote_average ?? 'n/a')} · TMDB #${escapeHtml(movie.tmdb_id)}</p>
                                <p class="text-xs uppercase tracking-wide text-muted">Описание (RU)</p>
                                <p class="text-sm leading-relaxed whitespace-pre-line">${escapeHtml(overview || 'Русское описание недоступно для этого фильма.')}</p>
                            </div>
                            <button
                                class="pick-match px-3 py-1.5 rounded-lg border border-amber-500/40 bg-amber-900/20 hover:bg-amber-800/30 text-xs"
                                data-tmdb-id="${escapeHtml(movie.tmdb_id)}"
                            >Use</button>
                        </div>
                    `;
                }).join('');

                resultsEl.querySelectorAll('.pick-match').forEach((pickButton) => {
                    pickButton.addEventListener('click', async () => {
                        if (!activeContext) {
                            return;
                        }

                        try {
                            const matchResponse = await fetch(activeContext.matchUrl, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': window.MoviesAnalyzer.csrf,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({ tmdb_id: Number(pickButton.dataset.tmdbId) }),
                            });

                            if (!matchResponse.ok) {
                                throw new Error('Applying match failed.');
                            }

                            removeRow(activeContext.id);
                            hideModal();
                            window.MoviesAnalyzer.toast('Manual match applied.', 'success');
                        } catch (error) {
                            window.MoviesAnalyzer.toast(error.message || 'Applying match failed.', 'error');
                        }
                    });
                });
            };

            const runTmdbSearch = async () => {
                if (!activeContext) {
                    return;
                }

                resultsEl.innerHTML = '<p class="text-muted">Searching TMDB...</p>';

                const year = Number.parseInt((yearInput.value || '').trim(), 10);
                const hasYear = Number.isInteger(year) && year >= 1900 && year <= 2099;

                if (!hasYear && (yearInput.value || '').trim() !== '') {
                    throw new Error('Year must be between 1900 and 2099.');
                }

                const response = await fetch(activeContext.searchUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.MoviesAnalyzer.csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        query: queryInput.value,
                        year: hasYear ? year : null,
                    }),
                });

                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || 'TMDB search failed.');
                }

                renderTmdbResults(payload.data);
            };

            document.querySelectorAll('.manual-search').forEach((button) => {
                button.addEventListener('click', () => openModal(button));
            });

            document.querySelectorAll('.refresh-one').forEach((button) => {
                button.addEventListener('click', async () => {
                    try {
                        await refreshMovie(button);
                    } catch (error) {
                        window.MoviesAnalyzer.toast(error.message || 'Refresh failed.', 'error');
                    }
                });
            });

            document.querySelectorAll('.manual-skip').forEach((button) => {
                button.addEventListener('click', async () => {
                    try {
                        const response = await fetch(button.dataset.url, {
                            method: 'PATCH',
                            headers: {
                                'X-CSRF-TOKEN': window.MoviesAnalyzer.csrf,
                                'Accept': 'application/json',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Skip failed.');
                        }

                        removeRow(button.dataset.id);
                        window.MoviesAnalyzer.toast('File marked as skipped.', 'info');
                    } catch (error) {
                        window.MoviesAnalyzer.toast(error.message || 'Skip failed.', 'error');
                    }
                });
            });

            closeButton.addEventListener('click', hideModal);

            searchButton.addEventListener('click', async () => {
                try {
                    await runTmdbSearch();
                } catch (error) {
                    resultsEl.innerHTML = '<p class="text-red-300">Search failed.</p>';
                    window.MoviesAnalyzer.toast(error.message || 'Search failed.', 'error');
                }
            });

            refreshAllButton.addEventListener('click', async () => {
                const buttons = Array.from(document.querySelectorAll('.refresh-one'));

                if (buttons.length === 0) {
                    window.MoviesAnalyzer.toast('No unmatched movies to refresh.', 'info');
                    return;
                }

                refreshAllButton.disabled = true;
                refreshAllStatus.textContent = `Refreshing 0/${buttons.length}...`;

                let processed = 0;
                let matched = 0;
                let uncertain = 0;
                let unmatched = 0;
                let failed = 0;

                for (const button of buttons) {
                    if (!button.isConnected) {
                        continue;
                    }

                    processed++;
                    refreshAllStatus.textContent = `Refreshing ${processed}/${buttons.length}...`;

                    try {
                        const payload = await refreshMovie(button, true);

                        if (payload.status === 'matched') {
                            matched++;
                        } else if (payload.status === 'uncertain') {
                            uncertain++;
                        } else if (payload.status === 'failed') {
                            failed++;
                        } else {
                            unmatched++;
                        }
                    } catch (error) {
                        failed++;
                    }

                    await wait(160);
                }

                refreshAllButton.disabled = false;
                refreshAllStatus.textContent = `Done. Matched ${matched}, uncertain ${uncertain}, still unmatched ${unmatched}, failed ${failed}.`;
                window.MoviesAnalyzer.toast(`Refresh complete: matched ${matched}, uncertain ${uncertain}, unmatched ${unmatched}, failed ${failed}.`, failed > 0 ? 'error' : 'success');
            });
        })();
    </script>
@endpush
