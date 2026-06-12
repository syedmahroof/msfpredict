<?php

namespace Database\Factories;

use App\Models\Prediction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prediction>
 */
class PredictionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $home = fake()->numberBetween(0, 4);
        $away = fake()->numberBetween(0, 4);

        return [
            'predicted_home_score' => $home,
            'predicted_away_score' => $away,
            'predicted_winner' => $home > $away ? 'home' : ($away > $home ? 'away' : 'draw'),
            'points_earned' => 0,
            'is_calculated' => false,
        ];
    }
}
