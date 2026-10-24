<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\StoreExpenseRequest;
use App\Models\Expense;
use App\Services\Expenses;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Expense::class);
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('from'), 'after_or_equal:from')],
            'category' => ['nullable', Rule::in(array_keys(Expense::CATEGORIES))],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'search' => ['nullable', 'string', 'max:150'],
        ]);
        $query = Expense::query()
            ->when(! empty($filters['from']), fn ($q) => $q->whereDate('paid_on', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn ($q) => $q->whereDate('paid_on', '<=', $filters['to']))
            ->when(! empty($filters['category']), fn ($q) => $q->where('category', $filters['category']))
            ->when(! empty($filters['payment_method']), fn ($q) => $q->where('payment_method', $filters['payment_method']))
            ->when(! empty($filters['search']), fn ($q) => $q->where(fn ($q) => $q->where('label', 'like', '%'.$filters['search'].'%')->orWhere('payee', 'like', '%'.$filters['search'].'%')->orWhere('reference', 'like', '%'.$filters['search'].'%')));

        return view('expenses.index', [
            'expenses' => (clone $query)->orderByDesc('paid_on')->orderByDesc('id')->paginate(20)->withQueryString(),
            'totals' => (clone $query)->selectRaw('currency, SUM(amount_minor) AS total_minor')->groupBy('currency')->orderBy('currency')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Expense::class);

        return view('expenses.create', ['submissionKey' => (string) Str::uuid()]);
    }

    public function store(StoreExpenseRequest $request, Expenses $expenses): RedirectResponse
    {
        $expense = $expenses->record($request->user(), $request->validated());

        return redirect()->route('expenses.show', $expense)->with('success', 'La dépense a été enregistrée et intégrée au rapport financier.');
    }

    public function show(Expense $expense): View
    {
        Gate::authorize('view', $expense);

        return view('expenses.show', compact('expense'));
    }
}
