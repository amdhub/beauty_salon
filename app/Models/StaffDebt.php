<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['employee_id', 'employee_name_snapshot', 'amount_minor', 'repaid_minor', 'currency', 'issued_on', 'due_on', 'reason', 'notes', 'created_by', 'actor_name_snapshot', 'idempotency_key', 'payment_method'])]
class StaffDebt extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'repaid_minor' => 'integer', 'issued_on' => 'date', 'due_on' => 'date', 'payment_method' => PaymentMethod::class];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(StaffRepayment::class);
    }

    public function getNumberAttribute(): string
    {
        return 'DET-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getBalanceMinorAttribute(): int
    {
        return $this->amount_minor - $this->repaid_minor;
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->balance_minor === 0) {
            return 'Soldée';
        }
        if ($this->due_on?->lt(today())) {
            return 'En retard';
        }

        return $this->repaid_minor > 0 ? 'Partiellement remboursée' : 'À rembourser';
    }
}
