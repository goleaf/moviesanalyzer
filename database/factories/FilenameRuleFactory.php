<?php

namespace Database\Factories;

use App\Models\FilenameRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FilenameRule>
 */
class FilenameRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rule_mode' => 'remove_token',
            'pattern' => $this->faker->word(),
            'replacement' => '',
            'is_regex' => false,
            'is_case_sensitive' => false,
            'whole_word' => true,
            'sort_order' => 100,
            'is_active' => true,
            'notes' => null,
        ];
    }
}
