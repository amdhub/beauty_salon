<?php

namespace Database\Factories;

use App\Models\SalonVisit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SalonVisit> */
class SalonVisitFactory extends Factory
{
    public function definition(): array
    {
        return ['checkout_key' => (string) Str::uuid(), 'customer_name_snapshot' => 'Client de passage', 'salon_name_snapshot' => 'Salon de test', 'cashier_name_snapshot' => 'Accueil', 'currency' => 'USD', 'subtotal' => '20.00', 'discount' => '0.00', 'total' => '20.00', 'created_by' => User::factory()];
    }
}
