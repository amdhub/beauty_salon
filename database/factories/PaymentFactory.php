<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\SalonVisit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return ['idempotency_key' => (string) Str::uuid(), 'salon_visit_id' => SalonVisit::factory(), 'amount' => '10.00', 'payment_method' => 'cash', 'received_by' => User::factory(), 'cashier_name_snapshot' => 'Accueil', 'payment_date' => now()];
    }
}
