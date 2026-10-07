<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['request_key', 'service_id', 'service_variant_id', 'service_name_snapshot', 'variant_name_snapshot', 'price_snapshot', 'currency', 'duration_minutes', 'customer_name', 'phone', 'email', 'preferred_at', 'scheduled_at', 'status', 'customer_notes', 'internal_notes', 'processed_by', 'processed_by_name'])]
class Reservation extends Model
{
    use HasFactory;

    public const STATUSES = ['pending' => 'En attente', 'confirmed' => 'Confirmée', 'rejected' => 'Refusée', 'cancelled' => 'Annulée', 'completed' => 'Terminée'];

    public const TRANSITIONS = ['pending' => ['confirmed', 'rejected', 'cancelled'], 'confirmed' => ['confirmed', 'completed', 'cancelled'], 'rejected' => [], 'cancelled' => [], 'completed' => []];

    protected function casts(): array
    {
        return ['preferred_at' => 'datetime', 'scheduled_at' => 'datetime', 'price_snapshot' => 'decimal:2'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function getNumberAttribute(): string
    {
        return 'RES-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status];
    }
}
