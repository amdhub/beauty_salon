<x-layout title="Stock du produit">
    <x-page-heading :title="$product->name" :description="$product->reference.' · '.\App\Models\ConsumableProduct::UNITS[$product->unit]">
        <a href="{{ route('products.index') }}" class="btn-secondary">Tous les produits</a>
        @can('update', $product)<a href="{{ route('products.edit', $product) }}" class="btn-secondary">Modifier</a>@endcan
    </x-page-heading>
    <div class="panel mb-6 flex flex-col justify-between gap-6 p-6 md:flex-row md:items-center">
        <div><p class="eyebrow">Stock disponible</p><p class="serif mt-2 text-4xl">{{ $product->stock }} <span class="text-lg">{{ $product->unit }}</span></p><p class="mt-2 text-sm text-muted">Seuil d’alerte : {{ $product->minimum_stock }} {{ $product->unit }}</p>
            @if(!$product->active)<p class="mt-3 text-sm text-muted">Produit inactif. Réactivez-le pour enregistrer un mouvement.</p>@elseif($product->low_stock)<p class="mt-3 text-sm font-semibold text-wine">{{ $product->stock === '0.000' ? 'Stock épuisé : approvisionnement nécessaire.' : 'Stock faible : pensez au prochain approvisionnement.' }}</p>@endif
        </div>
        @if($product->active)<div class="flex flex-wrap gap-3">
            @can('supply', $product)<a href="{{ route('products.movements.create', [$product, 'supply']) }}" class="btn"><x-icon name="plus"/>Approvisionner</a>@endcan
            @can('consume', $product)<a href="{{ route('products.movements.create', [$product, 'consume']) }}" class="btn-secondary">Enregistrer une consommation</a>@endcan
        </div>@endif
    </div>
    @if($product->notes)<p class="panel mb-6 whitespace-pre-line break-words p-5 text-sm text-muted">{{ $product->notes }}</p>@endif
    <h2 class="serif mb-4 text-2xl">Historique du produit</h2>
    @include('products.movement-table', ['movements' => $movements])
</x-layout>
