<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

trait TracksChanges
{
    protected static function bootTracksChanges(): void
    {
        static::saving(function (Model $model): void {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
                if (! $model->exists) {
                    $model->created_by = auth()->id();
                }
            }
        });
        static::deleting(function (Model $model): void {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
                $model->saveQuietly();
            }
        });
    }
}
