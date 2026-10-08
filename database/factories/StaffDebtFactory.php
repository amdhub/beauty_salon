<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StaffDebtFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(), 'employee_name_snapshot' => 'Membre test',
            'amount_minor' => 10000, 'repaid_minor' => 0, 'currency' => 'USD',
            'issued_on' => today()->subDay(), 'due_on' => null, 'reason' => 'Avance',
            'created_by' => User::factory(), 'actor_name_snapshot' => 'Responsable', 'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
