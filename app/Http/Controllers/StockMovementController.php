<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockMovementRequest;
use App\Models\ConsumableProduct;
use App\Models\Employee;
use App\Models\StockMovement;
use App\Services\InventoryMovements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ConsumableProduct::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'], 'type' => ['nullable', Rule::in(['supply', 'consume'])],
            'from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('from'), 'after_or_equal:from')],
        ]);
        $movements = StockMovement::query()
            ->when(! empty($filters['search']), fn ($q) => $q->where('product_name_snapshot', 'like', '%'.$filters['search'].'%'))
            ->when(! empty($filters['type']), fn ($q) => $q->where('type', $filters['type']))
            ->when(! empty($filters['from']), fn ($q) => $q->whereDate('movement_date', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn ($q) => $q->whereDate('movement_date', '<=', $filters['to']))
            ->latest('id')->paginate(25)->withQueryString();

        return view('products.movements', compact('movements', 'filters'));
    }

    public function create(ConsumableProduct $product, string $type): View
    {
        Gate::authorize($type, $product);

        return view('products.movement', [
            'product' => $product, 'type' => $type, 'idempotencyKey' => (string) Str::uuid(),
            'employees' => $type === 'consume' ? Employee::where('active', true)->orderBy('name')->get() : collect(),
        ]);
    }

    public function store(StockMovementRequest $request, ConsumableProduct $product, string $type, InventoryMovements $inventory): RedirectResponse
    {
        $inventory->record($product, $request->user(), $type, $request->validated());

        return redirect()->route('products.show', $product)->with('success', $type === 'supply' ? 'Approvisionnement enregistré. Le stock a été augmenté.' : 'Consommation enregistrée. Le stock a été diminué.');
    }
}
