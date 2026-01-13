<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Category;

class ProductFactory extends Factory
{
    protected $model = \App\Models\Product::class;

    public function definition(): array
    {
        $category = Category::inRandomOrder()->first() ?? Category::factory()->create();

        $baseNames = [
            'Laptop', 'Mouse', 'Keyboard', 'Monitor', 'Chair',
            'Desk', 'Lamp', 'Backpack', 'Book', 'Bottle',
            'Shoes', 'T-shirt', 'Headphones', 'Camera', 'Watch',
            'Tablet', 'Printer', 'Speaker', 'Phone', 'Bag',
            'Glasses', 'Notebook', 'Pen', 'Charger', 'Router'
        ];

        $name = $this->faker->unique()->randomElement($baseNames) . '-' . $this->faker->unique()->numberBetween(1, 1000);

        return [
            'name' => $name,
            'price' => $this->faker->randomFloat(2, 5, 200),
            'category_id' => $category->id,
        ];
    }
}
