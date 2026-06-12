<?php

namespace Database\Factories;

use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tournament>
 */
class TournamentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'FIFA World Cup 2026',
            'slug' => 'fifa-world-cup-2026',
            'year' => 2026,
            'start_date' => '2026-06-11',
            'end_date' => '2026-07-19',
            'is_active' => true,
        ];
    }
}
