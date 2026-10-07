<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Validation\ValidationException;

class CatalogPrice
{
    /** @return array{service_id:int, service_variant_id:?int, service_name_snapshot:string, variant_name_snapshot:?string, category_name_snapshot:string, price_snapshot:string} */
    public function quote(int $serviceId, ?int $variantId, bool $lock = false): array
    {
        $service = Service::query()->when($lock, fn ($q) => $q->lockForUpdate())->find($serviceId);
        $category = $service ? ServiceCategory::query()->when($lock, fn ($q) => $q->lockForUpdate())->find($service->service_category_id) : null;
        if (! $service?->active || ! $category?->active) {
            throw ValidationException::withMessages(['cart' => 'Cette prestation ou sa catégorie n’est plus disponible. Retirez-la du panier.']);
        }
        $variant = $variantId ? $service->variants()->when($lock, fn ($q) => $q->lockForUpdate())->find($variantId) : null;
        if ($variantId && ! $variant?->active) {
            throw ValidationException::withMessages(['cart' => 'Cette variante n’est plus disponible pour la prestation choisie.']);
        }
        if (! $variantId && $service->base_price === null) {
            throw ValidationException::withMessages(['cart' => 'Choisissez une variante tarifaire pour cette prestation.']);
        }

        return ['service_id' => $service->id, 'service_variant_id' => $variant?->id, 'service_name_snapshot' => $service->name, 'variant_name_snapshot' => $variant?->name, 'category_name_snapshot' => $category->name, 'price_snapshot' => $variant?->price ?? $service->base_price];
    }
}
