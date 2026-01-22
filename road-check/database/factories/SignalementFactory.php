<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Signalement>
 */
class SignalementFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement(['nouveau', 'en cours', 'termine']);

        return [
            'latitude' => -18.9 + fake()->randomFloat(4, 0, 0.2),
            'longitude' => 47.5 + fake()->randomFloat(4, 0, 0.2),
            'date_signalement' => now()->subDays(rand(0, 10)),
            'statut' => $status,
            'surface_m2' => fake()->randomFloat(2, 10, 500),
            'budget' => fake()->randomFloat(2, 1_000_000, 10_000_000),
        ];
    }
}
