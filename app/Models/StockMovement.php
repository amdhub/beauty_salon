<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['consumable_product_id', 'type', 'quantity', 'stock_after', 'product_name_snapshot', 'unit_snapshot', 'movement_date', 'supplier', 'reference', 'total_cost', 'currency', 'employee_id', 'employee_name_snapshot', 'notes', 'created_by', 'actor_name_snapshot', 'idempotency_key'])]
class StockMovement extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'stock_after' => 'decimal:3', 'total_cost' => 'decimal:2', 'movement_date' => 'date'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ConsumableProduct::class, 'consumable_product_id');
    }

    public function getLabelAttribute(): string
    {
        return $this->type === 'supply' ? 'Approvisionnement' : 'Consommation';
    }
}
