<?php

namespace App\Models;

use App\Services\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['checkout_key', 'customer_id', 'customer_name_snapshot', 'customer_phone_snapshot', 'salon_name_snapshot', 'cashier_name_snapshot', 'currency', 'source', 'status', 'subtotal', 'discount', 'total', 'notes', 'created_by'])]
class SalonVisit extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalonVisitItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function number(): Attribute
    {
        return Attribute::get(fn (): string => 'VIS-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT));
    }

    protected function receiptNumber(): Attribute
    {
        return Attribute::get(fn (): string => 'REC-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT));
    }

    protected function paidMinor(): Attribute
    {
        return Attribute::get(fn (): int => $this->payments->sum(fn (Payment $payment): int => Money::minor($payment->amount)));
    }

    protected function balanceMinor(): Attribute
    {
        return Attribute::get(fn (): int => Money::minor($this->total) - $this->paid_minor);
    }

    protected function paymentStatus(): Attribute
    {
        return Attribute::get(fn (): string => $this->balance_minor === 0 ? 'Payé' : ($this->paid_minor > 0 ? 'Partiellement payé' : 'À payer'));
    }
}
