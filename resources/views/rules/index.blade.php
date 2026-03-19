@extends('layouts.app')

@section('title', 'Filename Parser Rules · moviesanalyzer')

@section('content')
    <section class="space-y-6">
        <div class="card p-4 md:p-6">
            <h2 class="font-cinema text-3xl">Filename Parser Rules</h2>
            <p class="text-muted mt-2 text-sm">
                Rules are applied only inside Laravel for TMDB matching and saved parsing data. Original movie files on SMB are never renamed.
            </p>
        </div>

        <div class="card p-4 md:p-6 space-y-4">
            <div>
                <h3 class="font-cinema text-2xl">Preview Parsing</h3>
                <p class="text-muted mt-1 text-sm">Try replacements and removable tokens before rescanning.</p>
            </div>

            <form id="preview-form" class="flex flex-col md:flex-row gap-3">
                <input
                    id="preview-filename"
                    type="text"
                    value="{{ $previewSample }}"
                    class="flex-1 rounded-lg border border-cine bg-black/30 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500/30"
                    placeholder="The.Matrix.1999.1080p.BluRay.x264.mkv"
                >
                <button type="submit" class="px-5 py-2 rounded-lg border border-cine bg-card hover:bg-white/5">Preview</button>
            </form>

            <div id="preview-result" class="rounded-xl border border-cine bg-black/30 p-4 text-sm space-y-1">
                <p class="text-muted">Run preview to see cleaned title and TMDB search queries.</p>
            </div>
        </div>

        <div class="card p-4 md:p-6 space-y-4">
            <div>
                <h3 class="font-cinema text-2xl">Add Rule</h3>
                <p class="text-muted mt-1 text-sm">
                    `replace` updates filename text (for example, replace dots with spaces). `remove_token` removes matching tags such as `hdrip`.
                </p>
            </div>

            <form method="POST" action="{{ route('cineclean.rules.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label for="add-rule-mode" class="text-xs uppercase tracking-wider text-muted">Rule Mode</label>
                    <select id="add-rule-mode" name="rule_mode" class="mt-1 w-full rounded-lg border border-cine bg-black/30 px-3 py-2">
                        <option value="replace">replace</option>
                        <option value="remove_token">remove_token</option>
                    </select>
                </div>

                <div>
                    <label for="add-rule-pattern" class="text-xs uppercase tracking-wider text-muted">Pattern</label>
                    <input id="add-rule-pattern" name="pattern" type="text" class="mt-1 w-full rounded-lg border border-cine bg-black/30 px-3 py-2" placeholder="hdrip">
                </div>

                <div>
                    <label for="add-rule-replacement" class="text-xs uppercase tracking-wider text-muted">Replacement</label>
                    <input id="add-rule-replacement" name="replacement" type="text" value="" class="mt-1 w-full rounded-lg border border-cine bg-black/30 px-3 py-2" placeholder="space or empty">
                </div>

                <div>
                    <label for="add-rule-sort-order" class="text-xs uppercase tracking-wider text-muted">Sort Order</label>
                    <input id="add-rule-sort-order" name="sort_order" type="number" value="100" min="0" max="10000" class="mt-1 w-full rounded-lg border border-cine bg-black/30 px-3 py-2">
                </div>

                <div class="md:col-span-2">
                    <label for="add-rule-notes" class="text-xs uppercase tracking-wider text-muted">Notes</label>
                    <input id="add-rule-notes" name="notes" type="text" class="mt-1 w-full rounded-lg border border-cine bg-black/30 px-3 py-2" placeholder="Optional">
                </div>

                <div class="md:col-span-2 flex flex-wrap gap-4 text-sm">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_regex" value="1" class="rounded border-cine bg-black/30">
                        <span>Regex Pattern</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="whole_word" value="1" checked class="rounded border-cine bg-black/30">
                        <span>Whole Word</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_case_sensitive" value="1" class="rounded border-cine bg-black/30">
                        <span>Case Sensitive</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-cine bg-black/30">
                        <span>Active</span>
                    </label>
                </div>

                <div class="md:col-span-2">
                    <button type="submit" class="px-5 py-2 rounded-lg border border-amber-500/40 bg-amber-900/20 hover:bg-amber-800/30">Create Rule</button>
                </div>
            </form>
        </div>

        <div class="card p-4 md:p-6 space-y-4">
            <h3 class="font-cinema text-2xl">Default Removable Tokens</h3>
            <p class="text-muted text-sm">Click any token to prefill Add Rule form as `remove_token`.</p>

            <div class="flex flex-wrap gap-2">
                @foreach ($defaultTokens as $token)
                    <button type="button" class="token-chip text-xs px-2 py-1 rounded-full border border-cine bg-black/30 hover:bg-white/5" data-token="{{ $token }}">{{ $token }}</button>
                @endforeach
            </div>
        </div>

        <div class="card overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-black/40 text-xs uppercase tracking-wider text-muted">
                <tr>
                    <th class="px-4 py-3">Mode</th>
                    <th class="px-4 py-3">Pattern</th>
                    <th class="px-4 py-3">Replacement</th>
                    <th class="px-4 py-3">Flags</th>
                    <th class="px-4 py-3">Sort</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Notes</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rules as $rule)
                    <tr class="border-t border-cine align-top">
                        <td class="px-4 py-3 text-xs">{{ $rule->rule_mode }}</td>
                        <td class="px-4 py-3 text-xs font-mono break-all">{{ $rule->pattern }}</td>
                        <td class="px-4 py-3 text-xs">{{ $rule->replacement === '' ? '(empty)' : $rule->replacement }}</td>
                        <td class="px-4 py-3 text-xs">
                            {{ $rule->is_regex ? 'regex ' : '' }}
                            {{ $rule->whole_word ? 'whole_word ' : '' }}
                            {{ $rule->is_case_sensitive ? 'case_sensitive' : '' }}
                        </td>
                        <td class="px-4 py-3 text-xs">{{ $rule->sort_order }}</td>
                        <td class="px-4 py-3 text-xs">{{ $rule->is_active ? 'active' : 'disabled' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $rule->notes ?: '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <form method="POST" action="{{ route('cineclean.rules.destroy', $rule) }}" onsubmit="return confirm('Delete this rule?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1 rounded-lg border border-red-500/40 bg-red-900/20 hover:bg-red-800/30 text-xs">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr class="border-t border-cine/40">
                        <td colspan="8" class="px-4 py-3">
                            <form method="POST" action="{{ route('cineclean.rules.update', $rule) }}" class="grid grid-cols-1 md:grid-cols-8 gap-3 text-xs">
                                @csrf
                                @method('PATCH')
                                <select name="rule_mode" class="rounded-lg border border-cine bg-black/30 px-2 py-1">
                                    <option value="replace" @selected($rule->rule_mode === 'replace')>replace</option>
                                    <option value="remove_token" @selected($rule->rule_mode === 'remove_token')>remove_token</option>
                                </select>
                                <input name="pattern" type="text" value="{{ $rule->pattern }}" class="rounded-lg border border-cine bg-black/30 px-2 py-1">
                                <input name="replacement" type="text" value="{{ $rule->replacement }}" class="rounded-lg border border-cine bg-black/30 px-2 py-1">
                                <input name="sort_order" type="number" min="0" max="10000" value="{{ $rule->sort_order }}" class="rounded-lg border border-cine bg-black/30 px-2 py-1">
                                <input name="notes" type="text" value="{{ $rule->notes }}" class="rounded-lg border border-cine bg-black/30 px-2 py-1">
                                <label class="inline-flex items-center gap-1">
                                    <input type="checkbox" name="is_regex" value="1" @checked($rule->is_regex)>
                                    regex
                                </label>
                                <label class="inline-flex items-center gap-1">
                                    <input type="checkbox" name="whole_word" value="1" @checked($rule->whole_word)>
                                    whole_word
                                </label>
                                <div class="flex items-center gap-2">
                                    <label class="inline-flex items-center gap-1">
                                        <input type="checkbox" name="is_case_sensitive" value="1" @checked($rule->is_case_sensitive)>
                                        case
                                    </label>
                                    <label class="inline-flex items-center gap-1">
                                        <input type="checkbox" name="is_active" value="1" @checked($rule->is_active)>
                                        active
                                    </label>
                                    <button type="submit" class="px-3 py-1 rounded-lg border border-cine bg-card hover:bg-white/5">Save</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-muted">No parser rules found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card p-4">
            {{ $rules->links() }}
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            const modeInput = document.getElementById('add-rule-mode');
            const patternInput = document.getElementById('add-rule-pattern');
            const replacementInput = document.getElementById('add-rule-replacement');
            const previewForm = document.getElementById('preview-form');
            const previewFilenameInput = document.getElementById('preview-filename');
            const previewResult = document.getElementById('preview-result');

            document.querySelectorAll('.token-chip').forEach((chip) => {
                chip.addEventListener('click', () => {
                    modeInput.value = 'remove_token';
                    patternInput.value = chip.dataset.token || '';
                    replacementInput.value = '';
                    patternInput.focus();
                });
            });

            previewForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                try {
                    const response = await fetch(@json(route('cineclean.rules.preview')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': window.MoviesAnalyzer.csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            filename: previewFilenameInput.value,
                        }),
                    });

                    const payload = await response.json();

                    if (!response.ok) {
                        throw new Error('Preview failed.');
                    }

                    const queries = Array.isArray(payload.search_queries) ? payload.search_queries.join(', ') : '';

                    previewResult.innerHTML = `
                        <p><span class="text-muted">Base:</span> ${payload.base_name ?? ''}</p>
                        <p><span class="text-muted">Clean Title:</span> ${payload.clean_title ?? ''}</p>
                        <p><span class="text-muted">Year:</span> ${payload.release_year ?? 'n/a'}</p>
                        <p><span class="text-muted">Queries:</span> ${queries}</p>
                    `;
                } catch (error) {
                    window.MoviesAnalyzer.toast(error.message || 'Preview failed.', 'error');
                }
            });
        })();
    </script>
@endpush
