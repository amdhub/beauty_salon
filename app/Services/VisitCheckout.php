<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\SalonVisit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VisitCheckout
{
    public function __construct(private CatalogPrice $pricing, private VisitPayments $payments) {}

    public function create(User $cashier, array $data): SalonVisit
    {
        Gate::forUser($cashier)->authorize('create', SalonVisit::class);
        $data = Validator::make($data, [
            'checkout_key' => ['required', 'uuid'], 'customer_mode' => ['required', Rule::in(['walk_in', 'existing', 'new'])],
            'customer_id' => ['nullable', 'required_if:customer_mode,existing', 'integer'],
            'first_name' => ['nullable', 'required_if:customer_mode,new', 'string', 'max:100'], 'phone' => ['nullable', 'required_if:customer_mode,new', 'string', 'max:40'],
            'items' => ['required', 'array', 'min:1', 'max:50'], 'items.*.service_id' => ['required', 'integer'],
            'items.*.service_variant_id' => ['nullable', 'integer'], 'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.expected_price' => ['required', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/D'], 'items.*.employee_id' => ['nullable', 'integer'],
            'discount' => ['required', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/D'], 'amount' => ['nullable', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/D'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)], 'reference' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        return DB::transaction(function () use ($cashier, $data): SalonVisit {
            // Serialize repeated checkout submissions by the same cashier before checking the unique key.
            User::whereKey($cashier->id)->lockForUpdate()->firstOrFail();
            $existing = SalonVisit::where('checkout_key', $data['checkout_key'])->first();
            if ($existing) {
                abort_unless($existing->created_by === $cashier->id, 403);

                return $existing;
            }
            $lines = [];
            $subtotal = 0;
            foreach (collect($data['items'])->sortBy('service_id') as $item) {
                $quote = $this->pricing->quote($item['service_id'], $item['service_variant_id'] ?? null, true);
                if (Money::minor($quote['price_snapshot']) !== Money::minor((string) $item['expected_price'])) {
                    throw ValidationException::withMessages(['cart' => 'Un tarif a changé. Actualisez les tarifs du panier avant de confirmer le paiement.']);
                }
                $employee = null;
                if (! empty($item['employee_id'])) {
                    $employee = Employee::where('active', true)->lockForUpdate()->find($item['employee_id']);
                    if (! $employee) {
                        throw ValidationException::withMessages(['cart' => 'Un employé sélectionné n’est plus disponible.']);
                    }
                }
                $lineTotal = Money::minor($quote['price_snapshot']) * (int) $item['quantity'];
                $subtotal += $lineTotal;
                $lines[] = [...$quote, 'employee_id' => $employee?->id, 'employee_name_snapshot' => $employee?->name, 'quantity' => (int) $item['quantity'], 'subtotal' => Money::decimal($lineTotal), 'discount' => '0.00'];
            }
            $discount = Money::minor((string) $data['discount']);
            if ($discount > $subtotal || $subtotal > Money::MAX_MINOR) {
                throw ValidationException::withMessages(['discount' => 'Vérifiez la remise et le montant total du panier.']);
            }
            $total = $subtotal - $discount;
            $amount = isset($data['amount']) && $data['amount'] !== '' ? Money::minor((string) $data['amount']) : $total;
            if ($amount > $total) {
                throw ValidationException::withMessages(['amount' => 'Le montant encaissé ne doit pas dépasser le total à payer.']);
            }
            $customer = $this->customer($cashier, $data);
            $visit = SalonVisit::create([
                'checkout_key' => $data['checkout_key'], 'customer_id' => $customer?->id, 'customer_name_snapshot' => $customer?->full_name ?? 'Client de passage',
                'customer_phone_snapshot' => $customer?->phone, 'salon_name_snapshot' => config('salon.name'), 'cashier_name_snapshot' => $cashier->name, 'currency' => config('salon.currency'),
                'subtotal' => Money::decimal($subtotal), 'discount' => Money::decimal($discount), 'total' => Money::decimal($total), 'notes' => $data['notes'] ?? null, 'created_by' => $cashier->id,
            ]);
            $visit->items()->createMany($lines);
            if ($amount > 0) {
                $this->payments->record($visit, $cashier, ['idempotency_key' => $data['checkout_key'], 'amount' => Money::decimal($amount), 'payment_method' => $data['payment_method'], 'reference' => $data['reference'] ?? null]);
            }

            return $visit;
        }, 3);
    }

    private function customer(User $cashier, array $data): ?Customer
    {
        if ($data['customer_mode'] === 'walk_in') {
            return null;
        }
        Gate::forUser($cashier)->authorize('viewAny', Customer::class);
        if ($data['customer_mode'] === 'existing') {
            $customer = Customer::whereKey($data['customer_id'])->lockForUpdate()->first();
            if (! $customer) {
                throw ValidationException::withMessages(['customer_id' => 'Sélectionnez un client actif.']);
            }

            return $customer;
        }
        Gate::forUser($cashier)->authorize('create', Customer::class);
        $phone = preg_replace('/[\s().-]+/', '', trim($data['phone']));
        $phone = str_starts_with($phone, '00') ? '+'.substr($phone, 2) : $phone;
        Validator::make(['phone' => $phone], ['phone' => ['required', 'regex:/^\+?[0-9]{7,15}$/', Rule::unique('customers')]])->validate();

        return Customer::create(['first_name' => trim($data['first_name']), 'phone' => $phone]);
    }
}
