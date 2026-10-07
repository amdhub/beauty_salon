<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublicReservations
{
    /** @param array<string, mixed> $data Validated PublicBookingRequest input. */
    public function create(array $data): Reservation
    {
        return DB::transaction(function () use ($data): Reservation {
            if ($existing = Reservation::where('request_key', $data['request_key'])->first()) {
                return $existing;
            }
            [$serviceId, $option] = explode(':', $data['service_option']);
            $service = Service::whereKey($serviceId)->where('active', true)->lockForUpdate()->first();
            if (! $service || ! $service->category()->where('active', true)->whereNull('deleted_at')->exists()) {
                throw ValidationException::withMessages(['service_option' => 'Cette prestation n’est plus disponible. Choisissez une autre prestation.']);
            }
            $variant = $option === 'base' ? null : $service->variants()->whereKey($option)->where('active', true)->lockForUpdate()->first();
            if (($option !== 'base' && ! $variant) || ($option === 'base' && $service->base_price === null)) {
                throw ValidationException::withMessages(['service_option' => 'Ce tarif n’est plus disponible pour cette prestation.']);
            }

            return Reservation::query()->createOrFirst(['request_key' => $data['request_key']], [
                'service_id' => $service->id, 'service_variant_id' => $variant?->id,
                'service_name_snapshot' => $service->name, 'variant_name_snapshot' => $variant?->name,
                'price_snapshot' => $variant?->price ?? $service->base_price, 'currency' => config('salon.currency'),
                'duration_minutes' => $variant?->estimated_duration ?? $service->default_duration,
                'customer_name' => $data['customer_name'], 'phone' => $data['phone'], 'email' => $data['email'] ?? null,
                'preferred_at' => $data['preferred_at'], 'customer_notes' => $data['customer_notes'] ?? null, 'status' => 'pending',
            ]);
        }, 3);
    }
}
