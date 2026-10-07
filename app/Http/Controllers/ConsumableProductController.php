<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConsumableProductRequest;
use App\Models\ConsumableProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConsumableProductController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ConsumableProduct::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'low'])],
        ]);
        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? 'active';
        $products = ConsumableProduct::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('reference', 'like', '%'.$search.'%')))
            ->where('active', $status !== 'inactive')
            ->when($status === 'low', fn ($q) => $q->whereColumn('stock', '<=', 'minimum_stock'))
            ->orderBy('name')->orderBy('id')->paginate(12)->withQueryString();

        return view('products.index', [
            'products' => $products, 'search' => $search, 'status' => $status,
            'activeCount' => ConsumableProduct::where('active', true)->count(),
            'lowCount' => ConsumableProduct::where('active', true)->whereColumn('stock', '<=', 'minimum_stock')->count(),
            'emptyCount' => ConsumableProduct::where('active', true)->where('stock', 0)->count(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', ConsumableProduct::class);

        return view('products.form', ['product' => new ConsumableProduct(['active' => true, 'unit' => 'ml', 'minimum_stock' => '0.000']), 'unitLocked' => false]);
    }

    public function store(ConsumableProductRequest $request): RedirectResponse
    {
        $product = ConsumableProduct::create($request->validated());

        return redirect()->route('products.show', $product)->with('success', 'Produit créé. Enregistrez un premier approvisionnement pour renseigner le stock initial.');
    }

    public function show(ConsumableProduct $product): View
    {
        Gate::authorize('view', $product);

        return view('products.show', ['product' => $product, 'movements' => $product->movements()->latest('id')->paginate(20)]);
    }

    public function edit(ConsumableProduct $product): View
    {
        Gate::authorize('update', $product);

        return view('products.form', ['product' => $product, 'unitLocked' => $product->movements()->exists()]);
    }

    public function update(ConsumableProductRequest $request, ConsumableProduct $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product): void {
            $product = ConsumableProduct::whereKey($product->id)->lockForUpdate()->firstOrFail();
            if ($request->validated('unit') !== $product->unit && $product->movements()->exists()) {
                throw ValidationException::withMessages(['unit' => 'L’unité ne peut plus changer après le premier mouvement de stock.']);
            }
            $product->update($request->validated());
        }, 3);

        return redirect()->route('products.show', $product)->with('success', 'Produit mis à jour.');
    }
}
