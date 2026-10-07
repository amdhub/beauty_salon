<?php

namespace Database\Factories;

use App\Models\SalonVisit;
use App\Models\SalonVisitItem;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SalonVisitItem> */
class SalonVisitItemFactory extends Factory
{
    public function definition(): array
    {
        return ['salon_visit_id' => SalonVisit::factory(), 'service_id' => Service::factory(), 'service_name_snapshot' => 'Soin', 'category_name_snapshot' => 'Beauté', 'price_snapshot' => '20.00', 'quantity' => 1, 'subtotal' => '20.00'];
    }
}
