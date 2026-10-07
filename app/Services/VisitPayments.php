<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Models\SalonVisit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VisitPayments
{
    public function record(SalonVisit $visit, User $cashier, array $data): Payment
    {
        Gate::forUser($cashier)->authorize('recordPayment', $visit);
        $data = Validator::make($data, [
            'amount' => ['required', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/D'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)], 'reference' => ['nullable', 'string', 'max:255'], 'idempotency_key' => ['required', 'uuid'],
        ])->validate();

        return DB::transaction(function () use ($visit, $cashier, $data): Payment {
            $lockedVisit = SalonVisit::whereKey($visit->id)->lockForUpdate()->firstOrFail();
            $existing = Payment::where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                abort_unless($existing->salon_visit_id === $visit->id && $existing->received_by === $cashier->id, 403);

                return $existing;
            }
            $lockedVisit->load('payments');
            $amount = Money::minor((string) $data['amount']);
            if ($amount <= 0 || $amount > $lockedVisit->balance_minor) {
                throw ValidationException::withMessages(['amount' => 'Le paiement doit être supérieur à zéro et ne pas dépasser le solde restant.']);
            }

            return $lockedVisit->payments()->create([
                ...$data, 'amount' => Money::decimal($amount), 'received_by' => $cashier->id, 'cashier_name_snapshot' => $cashier->name, 'payment_date' => now(),
            ]);
        }, 3);
    }
}
