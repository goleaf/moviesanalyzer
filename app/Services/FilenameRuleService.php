<?php

namespace App\Services;

use App\Models\FilenameRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use Throwable;

class FilenameRuleService
{
    public const CACHE_KEY = 'moviesanalyzer.filename_rules.active';

    /**
     * @return Collection<int, FilenameRule>
     */
    public function activeRules(): Collection
    {
        if (! $this->canUseFacades()) {
            return collect();
        }

        try {
            if (! Schema::hasTable('filename_rules')) {
                return collect();
            }

            /** @var Collection<int, FilenameRule> $rules */
            $rules = Cache::remember(self::CACHE_KEY, now()->addMinutes(60), function (): Collection {
                return FilenameRule::query()
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
                    ->active()
                    ->ordered()
                    ->get();
            });

            return $rules;
        } catch (Throwable) {
            return collect();
        }
    }

    public function clearCache(): void
    {
        if (! $this->canUseFacades()) {
            return;
        }

        Cache::forget(self::CACHE_KEY);
    }

    public function applyReplacementRules(string $value): string
    {
        $result = $value;

        foreach ($this->activeRules()->where('rule_mode', 'replace') as $rule) {
            $result = $this->applyReplaceRule($result, $rule);
        }

        return trim(preg_replace('/\s+/u', ' ', $result) ?? $result);
    }

    public function shouldRemoveToken(string $normalizedToken): bool
    {
        if ($normalizedToken === '') {
            return false;
        }

        foreach ($this->activeRules()->where('rule_mode', 'remove_token') as $rule) {
            if ($this->tokenMatchesRule($normalizedToken, $rule)) {
                return true;
            }
        }

        return false;
    }

    private function applyReplaceRule(string $value, FilenameRule $rule): string
    {
        $replacement = $rule->replacement ?? '';

        if ($rule->is_regex) {
            $flags = $rule->is_case_sensitive ? 'u' : 'iu';
            $pattern = sprintf('/%s/%s', $rule->pattern, $flags);
            $updated = @preg_replace($pattern, $replacement, $value);

            return is_string($updated) ? $updated : $value;
        }

        $quotedPattern = preg_quote($rule->pattern, '/');

        if ($rule->whole_word) {
            $flags = $rule->is_case_sensitive ? 'u' : 'iu';
            $pattern = sprintf('/\\b%s\\b/%s', $quotedPattern, $flags);
            $updated = preg_replace($pattern, $replacement, $value);

            return is_string($updated) ? $updated : $value;
        }

        if ($rule->is_case_sensitive) {
            return str_replace($rule->pattern, $replacement, $value);
        }

        $updated = preg_replace(sprintf('/%s/iu', $quotedPattern), $replacement, $value);

        return is_string($updated) ? $updated : $value;
    }

    private function tokenMatchesRule(string $normalizedToken, FilenameRule $rule): bool
    {
        if ($rule->is_regex) {
            $flags = $rule->is_case_sensitive ? 'u' : 'iu';
            $pattern = sprintf('/%s/%s', $rule->pattern, $flags);

            return @preg_match($pattern, $normalizedToken) === 1;
        }

        $pattern = $rule->is_case_sensitive
            ? $rule->pattern
            : mb_strtolower($rule->pattern, 'UTF-8');
        $token = $rule->is_case_sensitive
            ? $normalizedToken
            : mb_strtolower($normalizedToken, 'UTF-8');

        if ($rule->whole_word) {
            return $token === $pattern;
        }

        return str_contains($token, $pattern);
    }

    private function canUseFacades(): bool
    {
        return Facade::getFacadeApplication() !== null;
    }
}
