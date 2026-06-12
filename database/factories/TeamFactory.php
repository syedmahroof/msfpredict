<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $country = fake()->country();

        return [
            'name' => $country,
            'slug' => \Illuminate\Support\Str::slug($country).'-'.fake()->unique()->numberBetween(1, 999),
            'country_code' => strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $country), 0, 3)),
            'group' => fake()->randomElement(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']),
        ];
    }
}
