<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ServiceVariant> */
class ServiceVariantFactory extends Factory
{
    public function definition(): array
    {
        return ['service_id' => Service::factory(), 'name' => 'Standard', 'price' => '20.00', 'active' => true, 'sort_order' => 0];
    }
}
