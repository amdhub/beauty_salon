<x-layout title="Produits & stocks">
    <x-page-heading title="Les réserves du salon" description="Suivez vos consommables, leurs approvisionnements et leur utilisation.">
        <a href="{{ route('products.movements.index') }}" class="btn-secondary">Historique des mouvements</a>
        @can('create', \App\Models\ConsumableProduct::class)<a href="{{ route('products.create') }}" class="btn"><x-icon name="plus"/>Ajouter un produit</a>@endcan
    </x-page-heading>
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="panel p-5"><p class="eyebrow">Produits actifs</p><p class="serif mt-2 text-3xl">{{ $activeCount }}</p></div>
        <a href="{{ route('products.index', ['status' => 'low']) }}" class="panel p-5"><p class="eyebrow">À réapprovisionner</p><p class="serif mt-2 text-3xl text-wine">{{ $lowCount }}</p><p class="mt-1 text-xs text-muted">Stock inférieur ou égal au seuil</p></a>
        <div class="panel p-5"><p class="eyebrow">En rupture</p><p class="serif mt-2 text-3xl">{{ $emptyCount }}</p></div>
    </div>
    <form method="GET" class="panel mb-6 flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
        <div class="field flex-1"><label for="search">Rechercher un produit</label><input id="search" name="search" value="{{ $search }}" placeholder="Nom ou référence…"></div>
        <div class="field sm:w-56"><label for="status">Afficher</label><select id="status" name="status"><option value="active" @selected($status === 'active')>Produits actifs</option><option value="low" @selected($status === 'low')>Stock faible / rupture</option><option value="inactive" @selected($status === 'inactive')>Produits inactifs</option></select></div>
        <button class="btn-secondary">Filtrer</button>
    </form>
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse($products as $product)
            <article class="panel flex flex-col p-6"><div class="mb-4"><x-list-number :number="$products->firstItem() + $loop->index"/></div>
                <div class="flex items-start justify-between gap-3"><p class="eyebrow break-all">{{ $product->reference }}</p><span class="{{ $product->active && !$product->low_stock ? 'badge' : 'badge-muted' }}">{{ !$product->active ? 'Inactif' : ($product->stock === '0.000' ? 'En rupture' : ($product->low_stock ? 'Stock faible' : 'Disponible')) }}</span></div>
                <h2 class="serif mt-4 break-words text-2xl"><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></h2>
                <p class="mt-5 text-2xl font-semibold">{{ $product->stock }} <span class="text-sm font-normal text-muted">{{ $product->unit }}</span></p>
                <p class="mt-1 text-xs text-muted">Seuil d’alerte : {{ $product->minimum_stock }} {{ $product->unit }}</p>
                <div class="mt-6 flex flex-wrap gap-3 border-t border-stone-100 pt-4">
                    <x-icon-action :href="route('products.show', $product)" :label="'Voir le stock de '.$product->name"/>
                    @if($product->active)
                        @can('supply', $product)<x-icon-action :href="route('products.movements.create', [$product, 'supply'])" :label="'Approvisionner : '.$product->name" icon="stock-in"/>@endcan
                        @can('consume', $product)<x-icon-action :href="route('products.movements.create', [$product, 'consume'])" :label="'Enregistrer une consommation : '.$product->name" icon="stock-out"/>@endcan
                    @endif
                </div>
            </article>
        @empty
            <div class="panel col-span-full"><x-empty-state title="Aucun produit trouvé" description="Ajoutez vos consommables ou adaptez les filtres de recherche." icon="layers"/></div>
        @endforelse
    </div>
    <div class="mt-6">{{ $products->links() }}</div>
</x-layout>
