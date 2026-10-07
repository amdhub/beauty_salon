<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ReservationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'request_key' => (string) Str::uuid(), 'service_id' => Service::factory(),
            'service_name_snapshot' => 'Soin du visage', 'price_snapshot' => '25.00', 'currency' => 'USD', 'duration_minutes' => 45,
            'customer_name' => fake()->name(), 'phone' => '+243990000001', 'preferred_at' => now()->addDay(), 'status' => 'pending',
        ];
    }
}
