<?php

namespace App\Models;

use Database\Factories\FilenameRuleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FilenameRule extends Model
{
    /** @use HasFactory<FilenameRuleFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'rule_mode',
        'pattern',
        'replacement',
        'is_regex',
        'is_case_sensitive',
        'whole_word',
        'sort_order',
        'is_active',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_regex' => 'boolean',
            'is_case_sensitive' => 'boolean',
            'whole_word' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
