<?php

namespace App\Models;

use App\Models\Concerns\TracksChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['first_name', 'last_name', 'phone', 'secondary_phone', 'email', 'gender', 'birth_date', 'address', 'notes', 'allergies', 'preferred_beautician_id'])]
class Customer extends Model
{
    use HasFactory, SoftDeletes, TracksChanges;

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    public function preferredBeautician(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'preferred_beautician_id')->withTrashed();
    }

    public function visits(): HasMany
    {
        return $this->hasMany(SalonVisit::class);
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => trim($this->first_name.' '.$this->last_name));
    }

    protected function number(): Attribute
    {
        return Attribute::get(fn (): string => 'CUS-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT));
    }
}
