<?php

namespace Database\Factories;

use App\Models\ConsumableProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StockMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'consumable_product_id' => ConsumableProduct::factory(), 'type' => 'supply', 'quantity' => '100.000', 'stock_after' => '100.000',
            'product_name_snapshot' => 'Shampooing', 'unit_snapshot' => 'ml', 'movement_date' => today(),
            'currency' => 'USD', 'created_by' => User::factory(), 'actor_name_snapshot' => 'Responsable', 'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
