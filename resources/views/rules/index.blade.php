@extends('layouts.app')

@section('title', 'Filename Parser Rules · moviesanalyzer')

@section('content')
    <section class="space-y-6">
        <div class="card p-4 md:p-6">
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <h2 class="font-cinema text-3xl">Filename Parser Rules</h2>
                    <p class="text-muted mt-2 text-sm">
                        Rules are applied only inside Laravel for TMDB matching and saved parsing data.
                        Original movie files on SMB are never renamed.
                    </p>
                </div>
                <a href="{{ route('cineclean.unmatched.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-cine bg-card hover:bg-white/5 text-xs md:text-sm">
                    Back to Unmatched
                </a>
            </div>
            <div class="mt-5 grid grid-cols-2 lg:grid-cols-6 gap-3">
                <div class="rounded-xl border border-cine bg-black/30 px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-muted">Total Rules</p>
                    <p class="text-xl font-semibold mt-1">{{ number_format($ruleStats['total']) }}</p>
                </div>
                <div class="rounded-xl border border-green-500/30 bg-green-900/10 px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-muted">Active</p>
                    <p class="text-xl font-semibold mt-1 text-green-300">{{ number_format($ruleStats['active']) }}</p>
                </div>
                <div class="rounded-xl border border-cine bg-black/30 px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-muted">Disabled</p>
                    <p class="text-xl font-semibold mt-1">{{ number_format($ruleStats['disabled']) }}</p>
                </div>
                <div class="rounded-xl border border-sky-500/30 bg-sky-900/10 px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-muted">Replace Rules</p>
                    <p class="text-xl font-semibold mt-1 text-sky-200">{{ number_format($ruleStats['replace']) }}</p>
                </div>
                <div class="rounded-xl border border-amber-500/30 bg-amber-900/10 px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-muted">Remove Token Rules</p>
                    <p class="text-xl font-semibold mt-1 text-amber-200">{{ number_format($ruleStats['remove_token']) }}</p>
                </div>
                <div class="rounded-xl border border-rose-500/30 bg-rose-900/10 px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-muted">Truncate Rules</p>
                    <p class="text-xl font-semibold mt-1 text-rose-200">{{ number_format($ruleStats['truncate_after_token']) }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
            <div class="card p-4 md:p-6 space-y-4 xl:col-span-3">
                <div>
                    <h3 class="font-cinema text-2xl">Preview Parsing</h3>
                    <p class="text-muted mt-1 text-sm">
                        Try replacements and removable tokens before rescanning unmatched movies.
                    </p>
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
                    <button id="preview-reset" type="button" class="px-5 py-2 rounded-lg border border-cine bg-black/30 hover:bg-white/5">Reset</button>
                </form>

                <div id="preview-result" class="rounded-xl border border-cine bg-black/30 p-4 text-sm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-muted">Original</p>
                            <p id="preview-original-value" class="mt-1 break-all font-mono text-xs">{{ $previewSample }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-muted">Base Name</p>
                            <p id="preview-base-value" class="mt-1 break-all text-sm text-muted">Run preview</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-muted">Clean Title</p>
                            <p id="preview-clean-value" class="mt-1 break-all text-base font-semibold">Run preview</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-muted">Release Year</p>
                            <p id="preview-year-value" class="mt-1 text-base">n/a</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-[11px] uppercase tracking-wide text-muted">TMDB Search Queries</p>
                            <div id="preview-queries-value" class="mt-2 flex flex-wrap gap-2">
                                <span class="inline-flex px-2 py-1 rounded-full border border-cine bg-black/30 text-xs text-muted">Run preview</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-4 md:p-6 space-y-4 xl:col-span-2">
                <div>
                    <h3 class="font-cinema text-2xl">Quick Templates</h3>
                    <p class="text-muted mt-1 text-sm">Prefill the Add Rule form with common cleanup patterns.</p>
                </div>

                <div class="space-y-3">
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-muted mb-2">Replace Templates</p>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="rule-template text-xs px-2 py-1 rounded-full border border-cine bg-black/30 hover:bg-white/5" data-mode="replace" data-pattern="." data-replacement=" " data-notes="Replace dots with spaces">
                                Dot → Space
                            </button>
                            <button type="button" class="rule-template text-xs px-2 py-1 rounded-full border border-cine bg-black/30 hover:bg-white/5" data-mode="replace" data-pattern="_" data-replacement=" " data-notes="Replace underscore with space">
                                Underscore → Space
                            </button>
                            <button type="button" class="rule-template text-xs px-2 py-1 rounded-full border border-cine bg-black/30 hover:bg-white/5" data-mode="replace" data-pattern="-" data-replacement=" " data-notes="Replace hyphen with space">
                                Hyphen → Space
                            </button>
                            <button type="button" class="rule-template text-xs px-2 py-1 rounded-full border border-cine bg-black/30 hover:bg-white/5" data-mode="truncate_after_token" data-pattern="от" data-replacement="" data-notes="Truncate trailing source text after token">
                                Truncate At `от`
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-muted mb-2">Remove Token Templates (from active rules)</p>
                        <div class="flex flex-wrap gap-2 max-h-44 overflow-auto pr-1">
                            @foreach ($removeTokenTemplates as $token)
                                <button type="button" class="token-chip text-xs px-2 py-1 rounded-full border border-cine bg-black/30 hover:bg-white/5" data-token="{{ $token }}">{{ $token }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-4 md:p-6 space-y-4">
            <div>
                <h3 class="font-cinema text-2xl">Add Rule</h3>
                <p class="text-muted mt-1 text-sm">
                    <span class="inline-block rounded-md border border-sky-500/40 bg-sky-900/20 px-2 py-0.5 text-[11px] text-sky-200 mr-1">replace</span>
                    updates filename text (example: `.` to space).
                    <span class="inline-block rounded-md border border-amber-500/40 bg-amber-900/20 px-2 py-0.5 text-[11px] text-amber-200 mx-1">remove_token</span>
                    removes matched tags such as `hdrip`.
                    <span class="inline-block rounded-md border border-rose-500/40 bg-rose-900/20 px-2 py-0.5 text-[11px] text-rose-200 mx-1">truncate_after_token</span>
                    stops parsing from matched token to filename end.
                </p>
            </div>

            <form method="POST" action="{{ route('cineclean.rules.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label for="add-rule-mode" class="text-xs uppercase tracking-wider text-muted">Rule Mode</label>
                    <select id="add-rule-mode" name="rule_mode" class="mt-1 w-full rounded-lg border border-cine bg-black/30 px-3 py-2">
                        <option value="replace">replace</option>
                        <option value="remove_token">remove_token</option>
                        <option value="truncate_after_token">truncate_after_token</option>
                    </select>
                </div>

                <div>
                    <label for="add-rule-pattern" class="text-xs uppercase tracking-wider text-muted">Pattern</label>
                    <input id="add-rule-pattern" name="pattern" type="text" class="mt-1 w-full rounded-lg border border-cine bg-black/30 px-3 py-2" placeholder="hdrip">
                </div>

                <div>
                    <label for="add-rule-replacement" class="text-xs uppercase tracking-wider text-muted">Replacement</label>
                    <input id="add-rule-replacement" name="replacement" type="text" value="" class="mt-1 w-full rounded-lg border border-cine bg-black/30 px-3 py-2" placeholder="Space or empty string">
                    <p id="replacement-help" class="mt-1 text-[11px] text-muted">Used only for `replace` rules.</p>
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
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h3 class="font-cinema text-2xl">Existing Rules</h3>
                    <p class="text-muted text-sm">Filter and edit rules without leaving the page.</p>
                </div>
                <p id="visible-rules-count" class="text-xs uppercase tracking-wide text-muted">Showing {{ $rules->count() }} on this page</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
                <input id="rules-search" type="text" class="rounded-lg border border-cine bg-black/30 px-3 py-2 text-sm" placeholder="Search pattern, replacement, notes">
                <select id="rules-mode-filter" class="rounded-lg border border-cine bg-black/30 px-3 py-2 text-sm">
                    <option value="all">All modes</option>
                    <option value="replace">replace</option>
                    <option value="remove_token">remove_token</option>
                    <option value="truncate_after_token">truncate_after_token</option>
                </select>
                <select id="rules-status-filter" class="rounded-lg border border-cine bg-black/30 px-3 py-2 text-sm">
                    <option value="all">All statuses</option>
                    <option value="active">Active</option>
                    <option value="disabled">Disabled</option>
                </select>
                <button id="clear-rules-filters" type="button" class="rounded-lg border border-cine bg-card px-3 py-2 text-sm hover:bg-white/5">Clear filters</button>
            </div>

            <div class="overflow-x-auto">
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
                    @php
                        $searchBlob = mb_strtolower(trim(($rule->pattern ?? '').' '.($rule->replacement ?? '').' '.($rule->notes ?? '')), 'UTF-8');
                    @endphp
                    <tr
                        class="border-t border-cine align-top"
                        data-rule-row
                        data-rule-id="{{ $rule->id }}"
                        data-mode="{{ $rule->rule_mode }}"
                        data-active="{{ $rule->is_active ? '1' : '0' }}"
                        data-search="{{ $searchBlob }}"
                    >
                        <td class="px-4 py-3 text-xs">
                            <span class="inline-flex px-2 py-1 rounded-full border {{ $rule->rule_mode === 'replace' ? 'border-sky-500/40 bg-sky-900/20 text-sky-200' : ($rule->rule_mode === 'truncate_after_token' ? 'border-rose-500/40 bg-rose-900/20 text-rose-200' : 'border-amber-500/40 bg-amber-900/20 text-amber-200') }}">
                                {{ $rule->rule_mode }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs font-mono break-all">{{ $rule->pattern }}</td>
                        <td class="px-4 py-3 text-xs font-mono">{{ $rule->replacement === '' ? '(empty)' : $rule->replacement }}</td>
                        <td class="px-4 py-3 text-xs">
                            <div class="flex flex-wrap gap-1">
                                @if ($rule->is_regex)
                                    <span class="inline-flex px-2 py-0.5 rounded border border-cine bg-black/30">regex</span>
                                @endif
                                @if ($rule->whole_word)
                                    <span class="inline-flex px-2 py-0.5 rounded border border-cine bg-black/30">whole_word</span>
                                @endif
                                @if ($rule->is_case_sensitive)
                                    <span class="inline-flex px-2 py-0.5 rounded border border-cine bg-black/30">case_sensitive</span>
                                @endif
                                @if (! $rule->is_regex && ! $rule->whole_word && ! $rule->is_case_sensitive)
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-xs">{{ $rule->sort_order }}</td>
                        <td class="px-4 py-3 text-xs">
                            <span class="inline-flex px-2 py-1 rounded-full border {{ $rule->is_active ? 'border-green-500/40 bg-green-900/20 text-green-300' : 'border-cine bg-black/30 text-muted' }}">
                                {{ $rule->is_active ? 'active' : 'disabled' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs">{{ $rule->notes ?: '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <button type="button" class="toggle-edit px-3 py-1 rounded-lg border border-cine bg-card hover:bg-white/5 text-xs" data-target="edit-rule-{{ $rule->id }}">
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('cineclean.rules.destroy', $rule) }}" onsubmit="return confirm('Delete this rule?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1 rounded-lg border border-red-500/40 bg-red-900/20 hover:bg-red-800/30 text-xs">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr id="edit-rule-{{ $rule->id }}" class="hidden border-t border-cine/40" data-rule-edit-for="{{ $rule->id }}">
                        <td colspan="8" class="px-4 py-3">
                            <form method="POST" action="{{ route('cineclean.rules.update', $rule) }}" class="grid grid-cols-1 md:grid-cols-8 gap-3 text-xs">
                                @csrf
                                @method('PATCH')
                                <select name="rule_mode" class="rounded-lg border border-cine bg-black/30 px-2 py-1">
                                    <option value="replace" @selected($rule->rule_mode === 'replace')>replace</option>
                                    <option value="remove_token" @selected($rule->rule_mode === 'remove_token')>remove_token</option>
                                    <option value="truncate_after_token" @selected($rule->rule_mode === 'truncate_after_token')>truncate_after_token</option>
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
            const notesInput = document.getElementById('add-rule-notes');
            const replacementHelp = document.getElementById('replacement-help');
            const previewForm = document.getElementById('preview-form');
            const previewResetButton = document.getElementById('preview-reset');
            const previewFilenameInput = document.getElementById('preview-filename');
            const previewOriginalValue = document.getElementById('preview-original-value');
            const previewBaseValue = document.getElementById('preview-base-value');
            const previewCleanValue = document.getElementById('preview-clean-value');
            const previewYearValue = document.getElementById('preview-year-value');
            const previewQueriesValue = document.getElementById('preview-queries-value');
            const rulesSearch = document.getElementById('rules-search');
            const rulesModeFilter = document.getElementById('rules-mode-filter');
            const rulesStatusFilter = document.getElementById('rules-status-filter');
            const clearRulesFilters = document.getElementById('clear-rules-filters');
            const visibleRulesCount = document.getElementById('visible-rules-count');

            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const updateReplacementState = () => {
                const isReplace = modeInput.value === 'replace';
                replacementInput.disabled = !isReplace;

                if (!isReplace) {
                    replacementInput.value = '';
                    replacementInput.placeholder = `Not used for ${modeInput.value}`;
                    replacementHelp.textContent = 'Disabled because only replace rules use a replacement value.';
                } else {
                    replacementInput.placeholder = 'Space or empty string';
                    replacementHelp.textContent = 'Used only for `replace` rules.';
                }
            };

            document.querySelectorAll('.token-chip').forEach((chip) => {
                chip.addEventListener('click', () => {
                    modeInput.value = 'remove_token';
                    patternInput.value = chip.dataset.token || '';
                    replacementInput.value = '';
                    notesInput.value = `Remove token: ${chip.dataset.token || ''}`;
                    updateReplacementState();
                    patternInput.focus();
                });
            });

            document.querySelectorAll('.rule-template').forEach((button) => {
                button.addEventListener('click', () => {
                    modeInput.value = button.dataset.mode || 'replace';
                    patternInput.value = button.dataset.pattern || '';
                    replacementInput.value = button.dataset.replacement || '';
                    notesInput.value = button.dataset.notes || '';
                    updateReplacementState();
                    patternInput.focus();
                });
            });

            modeInput.addEventListener('change', updateReplacementState);
            updateReplacementState();

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

                    const queries = Array.isArray(payload.search_queries) ? payload.search_queries : [];

                    previewOriginalValue.textContent = payload.original_filename ?? '';
                    previewBaseValue.textContent = payload.base_name ?? '';
                    previewCleanValue.textContent = payload.clean_title ?? '';
                    previewYearValue.textContent = payload.release_year ?? 'n/a';
                    previewQueriesValue.innerHTML = queries.length > 0
                        ? queries.map((query) => `<span class="inline-flex px-2 py-1 rounded-full border border-cine bg-black/30 text-xs">${escapeHtml(query)}</span>`).join('')
                        : '<span class="inline-flex px-2 py-1 rounded-full border border-cine bg-black/30 text-xs text-muted">No queries</span>';
                } catch (error) {
                    window.MoviesAnalyzer.toast(error.message || 'Preview failed.', 'error');
                }
            });

            previewResetButton.addEventListener('click', () => {
                previewFilenameInput.value = @json($previewSample);
                previewOriginalValue.textContent = @json($previewSample);
                previewBaseValue.textContent = 'Run preview';
                previewCleanValue.textContent = 'Run preview';
                previewYearValue.textContent = 'n/a';
                previewQueriesValue.innerHTML = '<span class="inline-flex px-2 py-1 rounded-full border border-cine bg-black/30 text-xs text-muted">Run preview</span>';
            });

            const ruleRows = Array.from(document.querySelectorAll('[data-rule-row]'));

            const applyRuleFilters = () => {
                const searchValue = (rulesSearch.value || '').trim().toLowerCase();
                const modeValue = rulesModeFilter.value;
                const statusValue = rulesStatusFilter.value;
                let visible = 0;

                ruleRows.forEach((row) => {
                    const matchesSearch = searchValue === '' || (row.dataset.search || '').includes(searchValue);
                    const matchesMode = modeValue === 'all' || (row.dataset.mode === modeValue);
                    const rowIsActive = row.dataset.active === '1';
                    const matchesStatus = statusValue === 'all'
                        || (statusValue === 'active' && rowIsActive)
                        || (statusValue === 'disabled' && !rowIsActive);
                    const isVisible = matchesSearch && matchesMode && matchesStatus;
                    const editRow = document.querySelector(`[data-rule-edit-for="${row.dataset.ruleId}"]`);

                    row.classList.toggle('hidden', !isVisible);

                    if (editRow) {
                        editRow.classList.add('hidden');
                    }

                    const toggleButton = row.querySelector('.toggle-edit');
                    if (toggleButton) {
                        toggleButton.textContent = 'Edit';
                    }

                    if (isVisible) {
                        visible++;
                    }
                });

                visibleRulesCount.textContent = `Showing ${visible} on this page`;
            };

            rulesSearch.addEventListener('input', applyRuleFilters);
            rulesModeFilter.addEventListener('change', applyRuleFilters);
            rulesStatusFilter.addEventListener('change', applyRuleFilters);
            clearRulesFilters.addEventListener('click', () => {
                rulesSearch.value = '';
                rulesModeFilter.value = 'all';
                rulesStatusFilter.value = 'all';
                applyRuleFilters();
            });

            document.querySelectorAll('.toggle-edit').forEach((button) => {
                button.addEventListener('click', () => {
                    const target = document.getElementById(button.dataset.target || '');
                    if (!target) {
                        return;
                    }

                    const willOpen = target.classList.contains('hidden');

                    document.querySelectorAll('[data-rule-edit-for]').forEach((row) => {
                        row.classList.add('hidden');
                    });

                    document.querySelectorAll('.toggle-edit').forEach((toggle) => {
                        toggle.textContent = 'Edit';
                    });

                    if (willOpen) {
                        target.classList.remove('hidden');
                        button.textContent = 'Hide';
                    }
                });
            });

            applyRuleFilters();
        })();
    </script>
@endpush
