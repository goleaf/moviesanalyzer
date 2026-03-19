<?php

namespace Database\Factories;

use App\Models\ScanLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScanLog>
 */
class ScanLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = now()->subMinutes(fake()->numberBetween(1, 10));

        return [
            'started_at' => $startedAt,
            'finished_at' => $startedAt->copy()->addMinutes(fake()->numberBetween(1, 10)),
            'total_files' => fake()->numberBetween(50, 1000),
            'matched' => fake()->numberBetween(10, 900),
            'unmatched' => fake()->numberBetween(0, 200),
            'status' => 'completed',
            'notes' => null,
        ];
    }
}
