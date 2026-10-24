<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\StaffDebt;
use App\Models\StaffRepayment;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialReport
{
    public const KINDS = ['sale' => 'Encaissement client', 'expense' => 'Dépense', 'staff_loan' => 'Avance / prêt à l’équipe', 'staff_repayment' => 'Remboursement de l’équipe'];

    public function movements(): Builder
    {
        $integerType = DB::getDriverName() === 'mysql' ? 'SIGNED' : 'INTEGER';
        $payments = Payment::query()->join('salon_visits', 'salon_visits.id', '=', 'payments.salon_visit_id')
            ->selectRaw("payments.id, 'sale' AS kind, DATE(payments.payment_date) AS movement_date, CAST(ROUND(payments.amount * 100, 0) AS {$integerType}) AS amount_minor, salon_visits.currency, payments.payment_method, 'in' AS direction, salon_visits.customer_name_snapshot AS label, payments.reference, payments.cashier_name_snapshot AS actor_name, payments.salon_visit_id AS source_id, payments.created_at")
            ->toBase();
        $expenses = Expense::query()->selectRaw("id, 'expense' AS kind, DATE(paid_on) AS movement_date, amount_minor, currency, payment_method, 'out' AS direction, label, reference, actor_name_snapshot AS actor_name, id AS source_id, created_at")->toBase();
        $loans = StaffDebt::query()->selectRaw("id, 'staff_loan' AS kind, DATE(issued_on) AS movement_date, amount_minor, currency, COALESCE(payment_method, 'unknown') AS payment_method, 'out' AS direction, employee_name_snapshot AS label, reason AS reference, actor_name_snapshot AS actor_name, id AS source_id, created_at")->toBase();
        $repayments = StaffRepayment::query()->join('staff_debts', 'staff_debts.id', '=', 'staff_repayments.staff_debt_id')
            ->selectRaw("staff_repayments.id, 'staff_repayment' AS kind, DATE(staff_repayments.paid_on) AS movement_date, staff_repayments.amount_minor, staff_debts.currency, staff_repayments.payment_method, 'in' AS direction, staff_debts.employee_name_snapshot AS label, staff_repayments.reference, staff_repayments.actor_name_snapshot AS actor_name, staff_repayments.staff_debt_id AS source_id, staff_repayments.created_at")
            ->toBase();

        return DB::query()->fromSub($payments->unionAll($expenses)->unionAll($loans)->unionAll($repayments), 'movements');
    }

    /** @param array{from: string, to: string, currency?: ?string, payment_method?: ?string} $filters */
    public function data(array $filters): array
    {
        $query = $this->movements()->where('movement_date', '<=', $filters['to'])
            ->when(! empty($filters['currency']), fn ($q) => $q->where('currency', $filters['currency']))
            ->when(! empty($filters['payment_method']), fn ($q) => $q->where('payment_method', $filters['payment_method']));
        $totals = $this->aggregate(clone $query, $filters['from']);
        if ($totals->isEmpty()) {
            $totals->push([
                'currency' => $filters['currency'] ?? config('salon.currency'), 'payment_method' => null,
                'opening_minor' => 0, 'income_minor' => 0, 'outgoing_minor' => 0, 'net_minor' => 0, 'closing_minor' => 0,
                'sales_minor' => 0, 'expenses_minor' => 0, 'loans_minor' => 0, 'repayments_minor' => 0,
            ]);
        }

        return [
            'totals' => $totals,
            'accounts' => $this->aggregate(clone $query, $filters['from'], true),
            'movements' => (clone $query)->where('movement_date', '>=', $filters['from'])->orderByDesc('movement_date')->orderByDesc('created_at')->orderBy('kind')->orderByDesc('id')->paginate(25)->withQueryString(),
            'currencies' => $this->movements()->distinct()->pluck('currency')->push(config('salon.currency'))->unique()->sort()->values(),
            'hasUnknownMethods' => $this->movements()->where('movement_date', '<=', $filters['to'])->where('payment_method', 'unknown')
                ->when(! empty($filters['currency']), fn ($q) => $q->where('currency', $filters['currency']))->exists(),
        ];
    }

    private function aggregate(Builder $query, string $from, bool $byMethod = false): Collection
    {
        $query->select('currency')->groupBy('currency')->orderBy('currency');
        if ($byMethod) {
            $query->addSelect('payment_method')->groupBy('payment_method')->orderBy('payment_method');
        }
        $query->selectRaw("SUM(CASE WHEN movement_date < ? AND direction = 'in' THEN amount_minor ELSE 0 END) AS prior_in_minor", [$from])
            ->selectRaw("SUM(CASE WHEN movement_date < ? AND direction = 'out' THEN amount_minor ELSE 0 END) AS prior_out_minor", [$from])
            ->selectRaw("SUM(CASE WHEN movement_date >= ? AND direction = 'in' THEN amount_minor ELSE 0 END) AS income_minor", [$from])
            ->selectRaw("SUM(CASE WHEN movement_date >= ? AND direction = 'out' THEN amount_minor ELSE 0 END) AS outgoing_minor", [$from]);
        foreach (['sale' => 'sales_minor', 'expense' => 'expenses_minor', 'staff_loan' => 'loans_minor', 'staff_repayment' => 'repayments_minor'] as $kind => $alias) {
            $query->selectRaw("SUM(CASE WHEN movement_date >= ? AND kind = ? THEN amount_minor ELSE 0 END) AS {$alias}", [$from, $kind]);
        }

        return $query->get()->map(function ($row): array {
            $opening = (int) $row->prior_in_minor - (int) $row->prior_out_minor;
            $net = (int) $row->income_minor - (int) $row->outgoing_minor;

            return [
                'currency' => $row->currency, 'payment_method' => $row->payment_method ?? null,
                'opening_minor' => $opening, 'income_minor' => (int) $row->income_minor, 'outgoing_minor' => (int) $row->outgoing_minor,
                'net_minor' => $net, 'closing_minor' => $opening + $net,
                'sales_minor' => (int) $row->sales_minor, 'expenses_minor' => (int) $row->expenses_minor,
                'loans_minor' => (int) $row->loans_minor, 'repayments_minor' => (int) $row->repayments_minor,
            ];
        });
    }
}
