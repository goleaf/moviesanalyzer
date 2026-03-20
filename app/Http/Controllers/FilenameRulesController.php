<?php

namespace App\Http\Controllers;

use App\Http\Requests\PreviewFilenameParsingRequest;
use App\Http\Requests\StoreFilenameRuleRequest;
use App\Http\Requests\UpdateFilenameRuleRequest;
use App\Models\FilenameRule;
use App\Services\FilenameParser;
use App\Services\FilenameRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class FilenameRulesController extends Controller
{
    public function __construct(private FilenameRuleService $filenameRuleService) {}

    public function index(Request $request): View
    {
        $rules = FilenameRule::query()
            ->select([
                'id',
                'rule_mode',
                'pattern',
                'replacement',
                'is_regex',
                'is_case_sensitive',
                'whole_word',
                'sort_order',
                'is_active',
                'notes',
            ])
            ->ordered()
            ->get();

        $totalRules = FilenameRule::query()->count();
        $activeRules = FilenameRule::query()->where('is_active', true)->count();
        $replaceRules = FilenameRule::query()->where('rule_mode', 'replace')->count();
        $removeTokenRules = FilenameRule::query()->where('rule_mode', 'remove_token')->count();
        $truncateAfterTokenRules = FilenameRule::query()->where('rule_mode', 'truncate_after_token')->count();

        /** @var Collection<int, string> $removeTokenTemplates */
        $removeTokenTemplates = FilenameRule::query()
            ->select(['pattern'])
            ->where('rule_mode', 'remove_token')
            ->where('is_active', true)
            ->where('is_regex', false)
            ->where('whole_word', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(160)
            ->pluck('pattern')
            ->unique()
            ->values();

        return view('rules.index', [
            'rules' => $rules,
            'removeTokenTemplates' => $removeTokenTemplates,
            'previewSample' => $request->string('sample', 'The.Matrix.1999.1080p.BluRay.x264.mkv')->toString(),
            'ruleStats' => [
                'total' => $totalRules,
                'active' => $activeRules,
                'disabled' => max(0, $totalRules - $activeRules),
                'replace' => $replaceRules,
                'remove_token' => $removeTokenRules,
                'truncate_after_token' => $truncateAfterTokenRules,
            ],
        ]);
    }

    public function store(StoreFilenameRuleRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['replacement'] = (string) ($payload['replacement'] ?? '');

        FilenameRule::query()->create($payload);
        $this->filenameRuleService->clearCache();

        return redirect()
            ->route('cineclean.rules.index')
            ->with('status', 'Filename rule created.');
    }

    public function update(UpdateFilenameRuleRequest $request, FilenameRule $filenameRule): RedirectResponse
    {
        $payload = $request->validated();
        $payload['replacement'] = (string) ($payload['replacement'] ?? '');

        $filenameRule->update($payload);
        $this->filenameRuleService->clearCache();

        return redirect()
            ->route('cineclean.rules.index')
            ->with('status', 'Filename rule updated.');
    }

    public function destroy(FilenameRule $filenameRule): RedirectResponse
    {
        $filenameRule->delete();
        $this->filenameRuleService->clearCache();

        return redirect()
            ->route('cineclean.rules.index')
            ->with('status', 'Filename rule removed.');
    }

    public function preview(PreviewFilenameParsingRequest $request, FilenameParser $filenameParser): JsonResponse
    {
        $parsed = $filenameParser->parse($request->string('filename')->toString());

        return response()->json([
            'original_filename' => $parsed->originalFilename,
            'base_name' => $parsed->baseName,
            'clean_title' => $parsed->cleanTitle,
            'release_year' => $parsed->releaseYear,
            'search_queries' => $parsed->searchQueries,
        ]);
    }
}
