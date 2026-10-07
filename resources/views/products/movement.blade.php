<x-layout :title="$type === 'supply' ? 'Approvisionnement' : 'Consommation'">
    <x-page-heading :title="$type === 'supply' ? 'Approvisionner le produit' : 'Enregistrer une consommation'" :description="$product->name.' · '.$product->reference"><a href="{{ route('products.show', $product) }}" class="btn-secondary">Retour au produit</a></x-page-heading>
    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_300px]">
        <form method="POST" action="{{ route('products.movements.store', [$product, $type]) }}" class="panel p-6 md:p-8" x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">
            <div class="grid gap-6 md:grid-cols-2">
                <x-field name="quantity" :label="'Quantité ('.$product->unit.')'" type="number" :min="$product->unit === 'piece' ? '1' : '0.001'" :step="$product->unit === 'piece' ? '1' : '0.001'" max="999999999.999" required/>
                <x-field name="movement_date" label="Date du mouvement" type="date" :value="today()->format('Y-m-d')" :max="today()->format('Y-m-d')" required/>
                @if($type === 'supply')
                    <x-field name="supplier" label="Fournisseur" maxlength="255" placeholder="Nom du fournisseur"/>
                    <x-field name="total_cost" :label="'Coût total de cet approvisionnement ('.config('salon.currency').')'" type="number" min="0" step="0.01" max="9999999999.99"/>
                @else
                    <div class="field"><label for="employee_id">Utilisé par</label><select id="employee_id" name="employee_id"><option value="">Non précisé</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
                @endif
                <x-field name="reference" :label="$type === 'supply' ? 'N° de facture / bon de livraison' : 'Référence (facultative)'" maxlength="255"/>
                <div class="md:col-span-2"><x-field name="notes" :label="$type === 'supply' ? 'Notes' : 'Motif / utilisation'" type="textarea" :required="$type === 'consume'" maxlength="3000" :placeholder="$type === 'supply' ? 'Livraison, stock initial…' : 'Shampooings clients, soins du visage…'"/></div>
            </div>
            <div class="mt-8 flex flex-wrap justify-end gap-3 border-t border-stone-100 pt-6"><a href="{{ route('products.show', $product) }}" class="btn-secondary">Annuler</a><button class="btn" :disabled="submitting" @disabled(!$product->active)>{{ $type === 'supply' ? 'Enregistrer l’approvisionnement' : 'Enregistrer la consommation' }}</button></div>
        </form>
        <aside class="panel p-6"><p class="eyebrow">Stock actuel</p><p class="serif mt-3 text-3xl">{{ $product->stock }} {{ $product->unit }}</p><p class="mt-4 text-sm leading-6 text-muted">{{ $type === 'supply' ? 'La quantité reçue sera ajoutée au stock dès l’enregistrement.' : 'La quantité utilisée sera déduite du stock. Une consommation supérieure au stock disponible sera refusée.' }}</p><p class="mt-4 text-xs leading-5 text-muted">Saisissez toutes les quantités en {{ $product->unit }}. Le mouvement sera conservé dans l’historique avec votre nom.</p>@unless($product->active)<p class="mt-4 font-semibold text-wine">Ce produit est inactif.</p>@endunless</aside>
    </div>
</x-layout>
