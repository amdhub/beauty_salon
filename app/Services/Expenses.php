<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class Expenses
{
    /** @param array<string, mixed> $data Validated StoreExpenseRequest input. */
    public function record(User $actor, array $data): Expense
    {
        Gate::forUser($actor)->authorize('create', Expense::class);

        return DB::transaction(function () use ($actor, $data): Expense {
            User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $existing = Expense::where('created_by', $actor->id)->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                return $existing;
            }

            return Expense::create([
                'label' => $data['label'], 'category' => $data['category'], 'amount_minor' => Money::minor((string) $data['amount']),
                'currency' => config('salon.currency'), 'paid_on' => $data['paid_on'], 'payment_method' => $data['payment_method'],
                'payee' => $data['payee'] ?? null, 'reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id, 'actor_name_snapshot' => $actor->name, 'idempotency_key' => $data['idempotency_key'],
            ]);
        }, 3);
    }
}
