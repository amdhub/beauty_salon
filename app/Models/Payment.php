<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['idempotency_key', 'salon_visit_id', 'amount', 'payment_method', 'reference', 'received_by', 'cashier_name_snapshot', 'payment_date'])]
class Payment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'payment_method' => PaymentMethod::class, 'payment_date' => 'datetime'];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(SalonVisit::class, 'salon_visit_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    protected function number(): Attribute
    {
        return Attribute::get(fn (): string => 'PAY-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT));
    }
}
