<?php

namespace App\Models;

use App\Models\Concerns\TracksChanges;
use App\Services\StockQuantity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'reference', 'unit', 'minimum_stock', 'active', 'notes'])]
class ConsumableProduct extends Model
{
    use HasFactory, TracksChanges;

    public const UNITS = ['piece' => 'Pièce', 'ml' => 'Millilitre (ml)', 'l' => 'Litre (l)', 'g' => 'Gramme (g)', 'kg' => 'Kilogramme (kg)'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'stock' => 'decimal:3', 'minimum_stock' => 'decimal:3'];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getLowStockAttribute(): bool
    {
        return StockQuantity::minor($this->stock) <= StockQuantity::minor($this->minimum_stock);
    }
}
