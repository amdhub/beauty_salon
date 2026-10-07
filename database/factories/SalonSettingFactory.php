<?php

namespace Database\Factories;

use App\Models\SalonSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalonSetting>
 */
class SalonSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => SalonSetting::RESERVATION_WHATSAPP_NUMBER,
            'value' => null,
        ];
    }
}
