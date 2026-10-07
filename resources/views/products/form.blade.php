<x-layout title="Produit consommable">
    <x-page-heading :title="$product->exists ? 'Modifier le produit' : 'Nouveau consommable'" description="Choisissez l’unité dans laquelle vous compterez toutes les entrées et sorties."><a href="{{ route('products.index') }}" class="btn-secondary">Retour aux produits</a></x-page-heading>
    <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" class="panel max-w-3xl p-6 md:p-8">
        @csrf @if($product->exists) @method('PUT') @endif
        <div class="grid gap-6 md:grid-cols-2">
            <x-field name="name" label="Nom du produit" :value="$product->name" required maxlength="150" placeholder="Shampooing hydratant"/>
            <x-field name="reference" label="Référence interne" :value="$product->reference" required maxlength="60" placeholder="SHAMP-001"/>
            <div class="field"><label for="unit">Unité de stock *</label><select id="unit" name="unit" required @disabled($unitLocked)>@foreach(\App\Models\ConsumableProduct::UNITS as $value => $label)<option value="{{ $value }}" @selected(old('unit', $product->unit) === $value)>{{ $label }}</option>@endforeach</select>
                @if($unitLocked)<input type="hidden" name="unit" value="{{ $product->unit }}"><p class="text-xs text-muted">L’unité est conservée après le premier mouvement pour garantir la cohérence du stock.</p>@else<p class="text-xs text-muted">Exemple : pour suivre un flacon de 500 ml, choisissez « ml » et approvisionnez 500.</p>@endif
            </div>
            <x-field name="minimum_stock" label="Seuil d’alerte" :value="$product->minimum_stock" type="number" min="0" max="999999999.999" step="0.001" required/>
            <div class="md:col-span-2"><x-field name="notes" label="Description / notes" :value="$product->notes" type="textarea" maxlength="3000"/></div>
            <div class="md:col-span-2"><x-active-field :value="$product->active"/></div>
        </div>
        @unless($product->exists)<p class="mt-6 rounded-xl bg-stone-50 p-4 text-sm text-muted">Le stock commence à zéro. Après la création, utilisez « Approvisionner » pour enregistrer les quantités déjà disponibles.</p>@endunless
        <div class="mt-8 flex justify-end gap-3 border-t border-stone-100 pt-6"><a href="{{ route('products.index') }}" class="btn-secondary">Annuler</a><button class="btn">Enregistrer le produit</button></div>
    </form>
</x-layout>
