<?php

namespace App\Models;

use App\Models\Concerns\TracksChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['service_id', 'name', 'description', 'price', 'estimated_duration', 'active', 'sort_order'])]
class ServiceVariant extends Model
{
    use HasFactory, SoftDeletes, TracksChanges;

    protected function casts(): array
    {
        return ['active' => 'boolean', 'price' => 'decimal:2'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }
}
