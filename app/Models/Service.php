<?php

namespace App\Models;

use App\Models\Concerns\TracksChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['service_category_id', 'name', 'description', 'default_duration', 'base_price', 'active', 'image_path', 'notes'])]
class Service extends Model
{
    use HasFactory, SoftDeletes, TracksChanges;

    protected function casts(): array
    {
        return ['active' => 'boolean', 'base_price' => 'decimal:2'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id')->withTrashed();
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ServiceVariant::class)->orderBy('sort_order')->orderBy('id');
    }
}
