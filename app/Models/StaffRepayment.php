<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['staff_debt_id', 'amount_minor', 'paid_on', 'payment_method', 'reference', 'notes', 'created_by', 'actor_name_snapshot', 'idempotency_key'])]
class StaffRepayment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'paid_on' => 'date', 'payment_method' => PaymentMethod::class];
    }

    public function debt(): BelongsTo
    {
        return $this->belongsTo(StaffDebt::class, 'staff_debt_id');
    }
}
