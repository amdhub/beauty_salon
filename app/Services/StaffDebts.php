<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\StaffDebt;
use App\Models\StaffRepayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class StaffDebts
{
    /** @param array<string, mixed> $data Validated StoreStaffDebtRequest input. */
    public function create(User $actor, array $data): StaffDebt
    {
        Gate::forUser($actor)->authorize('create', StaffDebt::class);

        return DB::transaction(function () use ($actor, $data): StaffDebt {
            User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $existing = StaffDebt::where('created_by', $actor->id)->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                if ($existing->employee_id !== (int) $data['employee_id']) {
                    throw ValidationException::withMessages(['idempotency_key' => 'Ce formulaire a déjà servi pour un autre membre. Rechargez la page.']);
                }

                return $existing;
            }
            $employee = Employee::whereKey($data['employee_id'])->where('active', true)->lockForUpdate()->first();
            if (! $employee) {
                throw ValidationException::withMessages(['employee_id' => 'Sélectionnez un membre actif de l’équipe.']);
            }
            $amount = Money::minor((string) $data['amount']);
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Le montant doit être supérieur à zéro.']);
            }

            return StaffDebt::create([
                'employee_id' => $employee->id, 'employee_name_snapshot' => $employee->name,
                'amount_minor' => $amount, 'repaid_minor' => 0, 'currency' => config('salon.currency'),
                'issued_on' => $data['issued_on'], 'due_on' => $data['due_on'] ?? null,
                'reason' => $data['reason'], 'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id, 'actor_name_snapshot' => $actor->name, 'idempotency_key' => $data['idempotency_key'],
            ]);
        }, 3);
    }

    /** @param array<string, mixed> $data Validated StoreStaffRepaymentRequest input. */
    public function repay(StaffDebt $debt, User $actor, array $data): StaffRepayment
    {
        Gate::forUser($actor)->authorize('repay', $debt);

        return DB::transaction(function () use ($debt, $actor, $data): StaffRepayment {
            User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $debt = StaffDebt::whereKey($debt->id)->lockForUpdate()->firstOrFail();
            $existing = StaffRepayment::where('created_by', $actor->id)->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                if ($existing->staff_debt_id !== $debt->id) {
                    throw ValidationException::withMessages(['idempotency_key' => 'Ce formulaire a déjà servi pour une autre dette. Rechargez la page.']);
                }

                return $existing;
            }
            $amount = Money::minor((string) $data['amount']);
            if ($amount <= 0 || $amount > $debt->balance_minor) {
                throw ValidationException::withMessages(['amount' => 'Le remboursement doit être supérieur à zéro et ne pas dépasser le solde restant.']);
            }
            if ($data['paid_on'] < $debt->issued_on->format('Y-m-d')) {
                throw ValidationException::withMessages(['paid_on' => 'Le remboursement ne peut pas précéder la date de la dette.']);
            }
            $repayment = $debt->repayments()->create([
                'amount_minor' => $amount, 'paid_on' => $data['paid_on'], 'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id, 'actor_name_snapshot' => $actor->name, 'idempotency_key' => $data['idempotency_key'],
            ]);
            $debt->repaid_minor += $amount;
            $debt->save();

            return $repayment;
        }, 3);
    }
}
