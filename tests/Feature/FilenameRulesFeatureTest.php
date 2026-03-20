<?php

use App\Models\FilenameRule;
use App\Services\FilenameRuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

it('renders parser rules page and manages rules', function (): void {
    $this->get(route('cineclean.rules.index'))
        ->assertSuccessful()
        ->assertSee('Filename Parser Rules');

    $this->post(route('cineclean.rules.store'), [
        'rule_mode' => 'remove_token',
        'pattern' => 'ultimatecut',
        'replacement' => '',
        'is_regex' => false,
        'is_case_sensitive' => false,
        'whole_word' => true,
        'sort_order' => 420,
        'is_active' => true,
        'notes' => 'custom cleanup token',
    ])->assertRedirect(route('cineclean.rules.index'));

    $rule = FilenameRule::query()
        ->select(['id', 'pattern'])
        ->where('pattern', 'ultimatecut')
        ->firstOrFail();

    $this->patch(route('cineclean.rules.update', $rule), [
        'rule_mode' => 'remove_token',
        'pattern' => 'ultimate-cut',
        'replacement' => '',
        'is_regex' => false,
        'is_case_sensitive' => false,
        'whole_word' => true,
        'sort_order' => 420,
        'is_active' => true,
        'notes' => 'updated token',
    ])->assertRedirect(route('cineclean.rules.index'));

    expect(FilenameRule::query()->whereKey($rule->id)->value('pattern'))->toBe('ultimate-cut');

    $this->delete(route('cineclean.rules.destroy', $rule))
        ->assertRedirect(route('cineclean.rules.index'));

    expect(FilenameRule::query()->whereKey($rule->id)->exists())->toBeFalse();
});

it('previews filename parsing using stored cleanup rules', function (): void {
    FilenameRule::query()->create([
        'rule_mode' => 'remove_token',
        'pattern' => 'ultimatecut',
        'replacement' => '',
        'is_regex' => false,
        'is_case_sensitive' => false,
        'whole_word' => true,
        'sort_order' => 10,
        'is_active' => true,
        'notes' => 'preview token',
    ]);

    app(FilenameRuleService::class)->clearCache();

    $this->postJson(route('cineclean.rules.preview'), [
        'filename' => 'The.Matrix.UltimateCut.1999.HDRip.mkv',
    ])
        ->assertSuccessful()
        ->assertJsonPath('clean_title', 'The Matrix')
        ->assertJsonPath('release_year', 1999);
});

it('renders all parser rules on a single page without pagination', function (): void {
    foreach (range(1, 60) as $index) {
        FilenameRule::query()->create([
            'rule_mode' => 'remove_token',
            'pattern' => sprintf('token-%02d', $index),
            'replacement' => '',
            'is_regex' => false,
            'is_case_sensitive' => false,
            'whole_word' => true,
            'sort_order' => 100 + $index,
            'is_active' => true,
            'notes' => 'pagination check',
        ]);
    }

    $response = $this->get(route('cineclean.rules.index'));

    $response
        ->assertSuccessful()
        ->assertSee('token-01')
        ->assertSee('token-60');

    expect($response->viewData('rules'))->toBeInstanceOf(Collection::class);
});
