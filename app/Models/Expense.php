<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['label', 'category', 'amount_minor', 'currency', 'paid_on', 'payment_method', 'payee', 'reference', 'notes', 'created_by', 'actor_name_snapshot', 'idempotency_key'])]
class Expense extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'supplies' => 'Approvisionnements', 'rent' => 'Loyer', 'utilities' => 'Eau, électricité et internet',
        'salary' => 'Salaires', 'transport' => 'Transport', 'maintenance' => 'Entretien et réparations', 'other' => 'Autres dépenses',
    ];

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'paid_on' => 'date', 'payment_method' => PaymentMethod::class];
    }

    public function getNumberAttribute(): string
    {
        return 'DEP-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category];
    }
}
