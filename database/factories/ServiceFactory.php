<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Service> */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return ['service_category_id' => ServiceCategory::factory(), 'name' => fake()->words(3, true), 'default_duration' => 60, 'active' => true];
    }
}
