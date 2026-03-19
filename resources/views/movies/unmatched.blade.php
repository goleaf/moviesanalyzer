@extends('layouts.app')

@section('title', 'Unmatched Movies · moviesanalyzer')

@section('content')
    <section class="space-y-6">
        <div class="card p-4 md:p-6">
            <h2 class="font-cinema text-3xl">Unmatched Movies</h2>
            <p class="text-muted mt-1 text-sm">Use Google Assist to research titles, then confirm TMDB match manually.</p>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-muted uppercase tracking-wide">Total unmatched: {{ $files->count() }}</p>
                <form method="POST" action="{{ route('cineclean.unmatched.rescan') }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-lg border border-amber-500/40 bg-amber-900/20 hover:bg-amber-800/30 text-xs md:text-sm">
                        Rescan Unmatched (Use Parser Rules)
                    </button>
                </form>
            </div>
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
                                    class="google-assist px-3 py-1.5 text-xs rounded-lg border border-amber-500/40 bg-amber-900/20 hover:bg-amber-800/30"
                                    data-id="{{ $file->id }}"
                                    data-search-url="{{ route('cineclean.unmatched.search', $file) }}"
                                    data-assist-url="{{ route('cineclean.unmatched.google-assist', $file) }}"
                                    data-match-url="{{ route('cineclean.unmatched.match', $file) }}"
                                    data-filename="{{ $file->filename }}"
                                    data-clean-title="{{ $file->parsed_clean_title }}"
                                >
                                    Google Assist
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
        <div class="max-w-2xl mx-auto mt-20 card p-6 space-y-4">
            <h3 class="font-cinema text-2xl">Manual Research &amp; TMDB Search</h3>
            <input id="manual-query" type="text" class="w-full rounded-lg border border-cine bg-black/30 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
            <div class="flex items-center justify-between">
                <button id="manual-close" type="button" class="px-4 py-2 rounded-lg border border-cine hover:bg-white/5">Cancel</button>
                <button id="manual-google-submit" type="button" class="px-4 py-2 rounded-lg border border-amber-500/40 bg-amber-900/20 hover:bg-amber-800/30">Google Research</button>
                <button id="manual-search-submit" type="button" class="px-4 py-2 rounded-lg border border-cine bg-card hover:bg-white/5">Search</button>
            </div>
            <div id="google-results" class="max-h-60 overflow-auto border border-cine rounded-xl p-3 text-sm space-y-2"></div>
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
            const googleAssistButton = document.getElementById('manual-google-submit');
            const closeButton = document.getElementById('manual-close');
            const googleResultsEl = document.getElementById('google-results');
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
                setTimeout(() => row.remove(), 300);
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

            const openModal = (button, autoAssist) => {
                activeContext = {
                    id: button.dataset.id,
                    searchUrl: button.dataset.searchUrl,
                    assistUrl: button.dataset.assistUrl,
                    matchUrl: button.dataset.matchUrl,
                };

                queryInput.value = button.dataset.cleanTitle || guessTitle(button.dataset.filename || '');
                googleResultsEl.innerHTML = '<p class="text-muted">Use Google Research to fetch title hints from web results.</p>';
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
                    return `
                        <div class="rounded-lg border border-cine p-3 flex items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold">${escapeHtml(movie.title || 'Untitled')} (${escapeHtml(movie.release_year || 'n/a')})</p>
                                <p class="text-xs text-muted">★ ${escapeHtml(movie.vote_average ?? 'n/a')} · TMDB #${escapeHtml(movie.tmdb_id)}</p>
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
                    body: JSON.stringify({ query: queryInput.value }),
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

                googleResultsEl.innerHTML = '<p class="text-muted">Running Google research...</p>';

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
        })();
    </script>
@endpush
