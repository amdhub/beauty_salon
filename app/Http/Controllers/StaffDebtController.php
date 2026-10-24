<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\StoreStaffDebtRequest;
use App\Models\Employee;
use App\Models\StaffDebt;
use App\Services\StaffDebts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffDebtController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', StaffDebt::class);
        $filters = $request->validate([
            'employee_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(['open', 'paid', 'overdue'])],
            'search' => ['nullable', 'string', 'max:150'],
        ]);
        $query = StaffDebt::query()
            ->when(! empty($filters['employee_id']), fn ($q) => $q->where('employee_id', $filters['employee_id']))
            ->when(! empty($filters['search']), fn ($q) => $q->where(fn ($q) => $q->where('employee_name_snapshot', 'like', '%'.$filters['search'].'%')->orWhere('reason', 'like', '%'.$filters['search'].'%')))
            ->when(($filters['status'] ?? '') === 'paid', fn ($q) => $q->whereColumn('repaid_minor', 'amount_minor'))
            ->when(in_array($filters['status'] ?? '', ['open', 'overdue'], true), fn ($q) => $q->whereColumn('repaid_minor', '<', 'amount_minor'))
            ->when(($filters['status'] ?? '') === 'overdue', fn ($q) => $q->whereDate('due_on', '<', today()));

        return view('staff-debts.index', [
            'debts' => (clone $query)->latest('id')->paginate(20)->withQueryString(),
            'totals' => (clone $query)->selectRaw('currency, SUM(amount_minor) AS total_minor, SUM(repaid_minor) AS total_repaid_minor, SUM(amount_minor - repaid_minor) AS total_balance_minor')->groupBy('currency')->orderBy('currency')->get(),
            'employees' => Employee::withTrashed()->orderBy('name')->get(['id', 'name', 'deleted_at']),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', StaffDebt::class);
        $selection = $request->validate(['employee_id' => ['nullable', 'integer', 'min:1']]);

        return view('staff-debts.create', [
            'employees' => Employee::where('active', true)->orderBy('name')->get(['id', 'name']),
            'selectedEmployee' => $selection['employee_id'] ?? null, 'submissionKey' => (string) Str::uuid(),
        ]);
    }

    public function store(StoreStaffDebtRequest $request, StaffDebts $debts): RedirectResponse
    {
        $debt = $debts->create($request->user(), $request->validated());

        return redirect()->route('staff-debts.show', $debt)->with('success', 'La dette du membre de l’équipe a été enregistrée.');
    }

    public function show(StaffDebt $debt): View
    {
        Gate::authorize('view', $debt);
        $debt->load('employee');

        return view('staff-debts.show', [
            'debt' => $debt, 'repayments' => $debt->repayments()->orderByDesc('paid_on')->orderByDesc('id')->paginate(15),
            'repaymentKey' => (string) Str::uuid(),
        ]);
    }

    public function updatePaymentMethod(Request $request, StaffDebt $debt): RedirectResponse
    {
        Gate::authorize('repay', $debt);
        $data = $request->validate(['payment_method' => ['required', Rule::enum(PaymentMethod::class)]]);
        DB::transaction(function () use ($debt, $data): void {
            $debt = StaffDebt::whereKey($debt->id)->lockForUpdate()->firstOrFail();
            if ($debt->payment_method !== null) {
                throw ValidationException::withMessages(['payment_method' => 'Le mode de versement de cette dette est déjà renseigné.']);
            }
            $debt->update($data);
        }, 3);

        return redirect()->route('staff-debts.show', $debt)->with('success', 'Le mode de versement est renseigné dans le rapport financier.');
    }
}
