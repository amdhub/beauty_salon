<x-layout title="Mouvements de stock">
    <x-page-heading title="Approvisionnements & consommations" description="L’historique des entrées et sorties de vos consommables, par ordre d’enregistrement."><a href="{{ route('products.index') }}" class="btn-secondary">Produits & stocks</a></x-page-heading>
    <form method="GET" class="panel mb-6 grid items-end gap-4 p-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-field name="search" label="Nom du produit" :value="$filters['search'] ?? ''"/>
        <div class="field"><label for="type">Type de mouvement</label><select id="type" name="type"><option value="">Tous les mouvements</option><option value="supply" @selected(($filters['type'] ?? '') === 'supply')>Approvisionnements</option><option value="consume" @selected(($filters['type'] ?? '') === 'consume')>Consommations</option></select></div>
        <x-field name="from" label="Du" type="date" :value="$filters['from'] ?? ''"/>
        <x-field name="to" label="Au" type="date" :value="$filters['to'] ?? ''"/>
        <div class="flex flex-wrap gap-2"><button class="btn">Filtrer</button><a href="{{ route('products.movements.index') }}" class="btn-secondary" aria-label="Effacer les filtres"><x-icon name="close"/></a></div>
    </form>
    @include('products.movement-table', ['movements' => $movements])
</x-layout>
