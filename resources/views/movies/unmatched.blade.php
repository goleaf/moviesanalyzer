@extends('layouts.app')

@section('title', 'Unmatched Movies · moviesanalyzer')

@section('content')
    <section class="space-y-6">
        <div class="card p-4 md:p-6">
            <h2 class="font-cinema text-3xl">Unmatched Movies</h2>
            <p class="text-muted mt-1 text-sm">Use Google MCP Assist to research titles, then confirm TMDB match manually.</p>
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
                        <td class="px-4 py-3 font-mono text-xs md:text-sm break-all">{{ $file->filename }}</td>
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
                                    class="google-assist px-3 py-1.5 text-xs rounded-lg border border-amber-500/40 bg-amber-900/20 hover:bg-amber-800/30"
                                    data-id="{{ $file->id }}"
                                    data-search-url="{{ route('cineclean.unmatched.search', $file) }}"
                                    data-assist-url="{{ route('cineclean.unmatched.google-assist', $file) }}"
                                    data-match-url="{{ route('cineclean.unmatched.match', $file) }}"
                                    data-filename="{{ $file->filename }}"
                                    data-clean-title="{{ $file->parsed_clean_title }}"
                                    data-release-year="{{ $file->parsed_release_year }}"
                                >
                                    Google MCP
                                </button>
                                <button
                                    type="button"
                                    class="manual-search px-3 py-1.5 text-xs rounded-lg border border-cine bg-card hover:bg-white/5"
                                    data-id="{{ $file->id }}"
                                    data-search-url="{{ route('cineclean.unmatched.search', $file) }}"
                                    data-assist-url="{{ route('cineclean.unmatched.google-assist', $file) }}"
                                    data-match-url="{{ route('cineclean.unmatched.match', $file) }}"
                                    data-filename="{{ $file->filename }}"
                                    data-clean-title="{{ $file->parsed_clean_title }}"
                                    data-release-year="{{ $file->parsed_release_year }}"
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
            <input id="manual-query" type="text" class="w-full rounded-lg border border-cine bg-black/30 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
            <div class="flex items-center justify-between">
                <button id="manual-close" type="button" class="px-4 py-2 rounded-lg border border-cine hover:bg-white/5">Cancel</button>
                <button id="manual-google-submit" type="button" class="px-4 py-2 rounded-lg border border-amber-500/40 bg-amber-900/20 hover:bg-amber-800/30">Google Research</button>
                <button id="manual-search-submit" type="button" class="px-4 py-2 rounded-lg border border-cine bg-card hover:bg-white/5">Search</button>
            </div>
            <div id="google-results" class="max-h-[26vh] overflow-auto border border-cine rounded-xl p-3 text-sm space-y-2"></div>
            <div id="manual-results" class="max-h-[56vh] overflow-auto border border-cine rounded-xl p-3 text-sm space-y-3"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('manual-modal');
            const queryInput = document.getElementById('manual-query');
            const searchButton = document.getElementById('manual-search-submit');
            const googleAssistButton = document.getElementById('manual-google-submit');
            const closeButton = document.getElementById('manual-close');
            const googleResultsEl = document.getElementById('google-results');
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
                googleResultsEl.innerHTML = '';
                resultsEl.innerHTML = '';
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

            const openModal = (button, autoAssist) => {
                activeContext = {
                    id: button.dataset.id,
                    searchUrl: button.dataset.searchUrl,
                    assistUrl: button.dataset.assistUrl,
                    matchUrl: button.dataset.matchUrl,
                    releaseYear: button.dataset.releaseYear ? Number(button.dataset.releaseYear) : null,
                };

                queryInput.value = button.dataset.cleanTitle || guessTitle(button.dataset.filename || '');
                googleResultsEl.innerHTML = '<p class="text-muted">Use Google MCP Research to fetch title hints from web results.</p>';
                resultsEl.innerHTML = '<p class="text-muted">Search TMDB manually or use Google suggestions first.</p>';
                modal.classList.remove('hidden');

                if (autoAssist) {
                    runGoogleAssist().catch((error) => {
                        googleResultsEl.innerHTML = '<p class="text-red-300">Google research failed.</p>';
                        window.MoviesAnalyzer.toast(error.message || 'Google research failed.', 'error');
                    });
                }
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

            const renderGoogleResearch = (payload) => {
                const titleSuggestions = Array.isArray(payload.title_suggestions) ? payload.title_suggestions : [];
                const googleResults = Array.isArray(payload.google_results) ? payload.google_results : [];
                const tmdbCandidates = Array.isArray(payload.tmdb_candidates) ? payload.tmdb_candidates : [];

                const suggestionHtml = titleSuggestions.length === 0
                    ? '<p class="text-muted">No title suggestions extracted.</p>'
                    : `
                        <div class="flex flex-wrap gap-2">
                            ${titleSuggestions.map((title) => `
                                <button type="button" class="google-suggestion px-2 py-1 rounded-lg border border-cine hover:bg-white/5 text-xs" data-title="${encodeURIComponent(title)}">
                                    ${escapeHtml(title)}
                                </button>
                            `).join('')}
                        </div>
                    `;

                const resultHtml = googleResults.length === 0
                    ? '<p class="text-muted">No Google result snippets available.</p>'
                    : googleResults.map((result) => `
                        <div class="rounded-lg border border-cine/60 p-2">
                            <p class="font-medium text-sm">${escapeHtml(result.title || 'Untitled result')}</p>
                            <p class="text-xs text-muted">${escapeHtml(result.snippet || '')}</p>
                            <a href="${safeUrl(result.link)}" target="_blank" rel="noopener noreferrer" class="text-xs text-amber-300 hover:text-amber-200">
                                ${escapeHtml(result.display_link || result.link || 'Open source')}
                            </a>
                        </div>
                    `).join('');

                googleResultsEl.innerHTML = `
                    <div class="space-y-2">
                        <p class="text-xs text-muted">Provider: ${escapeHtml(payload.provider || 'google')} · Query: ${escapeHtml(payload.query || '')}</p>
                        ${payload.message ? `<p class="text-xs text-amber-300">${escapeHtml(payload.message)}</p>` : ''}
                        <div>
                            <p class="text-xs uppercase tracking-wide text-muted mb-1">Suggested Titles</p>
                            ${suggestionHtml}
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-muted mb-1">Google Results</p>
                            <div class="space-y-2">${resultHtml}</div>
                        </div>
                    </div>
                `;

                googleResultsEl.querySelectorAll('.google-suggestion').forEach((button) => {
                    button.addEventListener('click', () => {
                        queryInput.value = decodeURIComponent(button.dataset.title || '');
                    });
                });

                if (tmdbCandidates.length > 0) {
                    renderTmdbResults(tmdbCandidates);
                }
            };

            const runTmdbSearch = async () => {
                if (!activeContext) {
                    return;
                }

                resultsEl.innerHTML = '<p class="text-muted">Searching TMDB...</p>';

                const response = await fetch(activeContext.searchUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.MoviesAnalyzer.csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        query: queryInput.value,
                        year: activeContext.releaseYear,
                    }),
                });

                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || 'TMDB search failed.');
                }

                renderTmdbResults(payload.data);
            };

            const runGoogleAssist = async () => {
                if (!activeContext) {
                    return;
                }

                googleResultsEl.innerHTML = '<p class="text-muted">Running Google MCP research...</p>';

                const response = await fetch(activeContext.assistUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.MoviesAnalyzer.csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ query: queryInput.value }),
                });

                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || 'Google research failed.');
                }

                renderGoogleResearch(payload);
            };

            document.querySelectorAll('.manual-search').forEach((button) => {
                button.addEventListener('click', () => openModal(button, false));
            });

            document.querySelectorAll('.google-assist').forEach((button) => {
                button.addEventListener('click', () => openModal(button, true));
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

            googleAssistButton.addEventListener('click', async () => {
                try {
                    await runGoogleAssist();
                } catch (error) {
                    googleResultsEl.innerHTML = '<p class="text-red-300">Google research failed.</p>';
                    window.MoviesAnalyzer.toast(error.message || 'Google research failed.', 'error');
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
