<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = \App\Models\Category::class;

    public function definition(): array
    {
        $names = [
            'Electronics', 'Home', 'Fashion', 'Books', 'Sports',
            'Toys', 'Health', 'Beauty', 'Food', 'Office',
            'Garden', 'Tools', 'Pets', 'Music', 'Games'
        ];

        $name = $this->faker->unique()->randomElement($names);

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1, 1000),
            'description' => $this->faker->sentence(),
            'is_active' => true,
            'color' => $this->faker->hexColor(),
        ];
    }
}
