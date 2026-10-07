<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ConsumableProductFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->words(3, true), 'reference' => fake()->unique()->bothify('PROD-####??'), 'unit' => 'ml', 'stock' => '0.000', 'minimum_stock' => '100.000', 'active' => true];
    }
}
