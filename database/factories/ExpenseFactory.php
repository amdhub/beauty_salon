<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => 'Loyer du salon', 'category' => 'rent', 'amount_minor' => 10000,
            'currency' => 'USD', 'paid_on' => today(), 'payment_method' => 'cash',
            'created_by' => User::factory(), 'actor_name_snapshot' => 'Responsable', 'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
