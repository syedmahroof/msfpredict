<?php

namespace Database\Factories;

use App\Models\Fixture;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fixture>
 */
class FixtureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'round' => fake()->randomElement(['group_stage', 'round_of_16', 'quarter_final', 'semi_final', 'final']),
            'group' => fake()->randomElement(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', null]),
            'match_day' => fake()->numberBetween(1, 3),
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+30 days'),
            'status' => 'upcoming',
        ];
    }

    public function live(): static
    {
        return $this->state(['status' => 'live', 'home_score' => fake()->numberBetween(0, 3), 'away_score' => fake()->numberBetween(0, 3)]);
    }

    public function finished(): static
    {
        return $this->state([
            'status' => 'finished',
            'scheduled_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'home_score' => fake()->numberBetween(0, 4),
            'away_score' => fake()->numberBetween(0, 4),
            'winner' => fn (array $attrs) => $attrs['home_score'] > $attrs['away_score'] ? 'home' : ($attrs['away_score'] > $attrs['home_score'] ? 'away' : 'draw'),
            'points_calculated' => true,
        ]);
    }
}
