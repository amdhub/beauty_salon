<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['salon_visit_id', 'service_id', 'service_variant_id', 'employee_id', 'service_name_snapshot', 'variant_name_snapshot', 'category_name_snapshot', 'employee_name_snapshot', 'price_snapshot', 'quantity', 'discount', 'subtotal'])]
class SalonVisitItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['price_snapshot' => 'decimal:2', 'discount' => 'decimal:2', 'subtotal' => 'decimal:2', 'quantity' => 'integer'];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(SalonVisit::class, 'salon_visit_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ServiceVariant::class, 'service_variant_id')->withTrashed();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }
}
