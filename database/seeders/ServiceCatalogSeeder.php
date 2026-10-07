<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceVariant;
use Illuminate\Database\Seeder;

class ServiceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'Locs' => [
                ['Micro Locs Installation', [100, 80, 70, 50]], ['Sister Locs Installation', [200]],
                ['Locs Extension Installation / Placage Locs Extensions', [50, 60, 70, 80, 100]],
                ['Micro Locs / Sister Locs Retouch', [15, 20, 25, 30, 35]], ['Locs Repair / Réparation Locs', [10, 15]],
                ['Dread Locs Retouch', [10, 15, 20]], ['Locs Washing', [7]], ['Scalp Washing', [5]],
            ],
            'Manucure' => [
                ['Long Polygel', [20]], ['Medium Polygel', [15]], ['Short Polygel', [10]],
                ['Medium Acrylic', [30]], ['Short Acrylic', [25]], ['Treatment', [15]],
                ['Nail extension / Placage', [10]], ['Nail polish application / Pose vernis', [5]],
            ],
            'Pédicure' => [['Pedicure without nail polish', [10]], ['Complete pedicure including scrub and nail polish', [15]]],
            'Réparation / Entretien des ongles' => [['Gel removal', [3]], ['Nail extension repair', [2]], ['Polygel repair', [2]], ['Gel nail repair', [1]]],
            'Cheveux' => [],
        ];
        foreach ($catalog as $categoryName => $services) {
            $category = ServiceCategory::withTrashed()->firstOrCreate(['name' => $categoryName], ['sort_order' => array_search($categoryName, array_keys($catalog))]);
            foreach ($services as [$name,$prices]) {
                $service = Service::withTrashed()->firstOrCreate(['service_category_id' => $category->id, 'name' => $name], ['default_duration' => 60, 'notes' => 'Durée indicative : à ajuster selon la prestation.']);
                if (! $service->wasRecentlyCreated) {
                    continue;
                }
                foreach ($prices as $index => $price) {
                    ServiceVariant::create(['service_id' => $service->id, 'name' => count($prices) === 1 ? 'Standard' : 'Option '.($index + 1), 'price' => (string) $price, 'sort_order' => $index]);
                }
            }
        }
    }
}
