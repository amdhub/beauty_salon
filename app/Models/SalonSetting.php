<?php

namespace App\Models;

use App\Models\Concerns\TracksChanges;
use Database\Factories\SalonSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value'])]
class SalonSetting extends Model
{
    /** @use HasFactory<SalonSettingFactory> */
    use HasFactory, TracksChanges;

    public const RESERVATION_WHATSAPP_NUMBER = 'reservation_whatsapp_number';
}
