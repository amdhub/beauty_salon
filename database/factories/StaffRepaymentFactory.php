<?php

namespace Database\Factories;

use App\Models\StaffDebt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StaffRepaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'staff_debt_id' => StaffDebt::factory(), 'amount_minor' => 2500,
            'paid_on' => today(), 'payment_method' => 'cash',
            'created_by' => User::factory(), 'actor_name_snapshot' => 'Responsable', 'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
