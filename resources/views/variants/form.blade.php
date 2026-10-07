<x-layout title="Tarif"><x-page-heading :title="$variant->exists ? 'Modifier un tarif' : 'Ajouter un tarif'" :description="$service->name"/>
<form method="POST" action="{{ $variant->exists ? route('services.variants.update',[$service,$variant]) : route('services.variants.store',$service) }}" class="panel max-w-3xl p-7">@csrf @if($variant->exists) @method('PUT') @endif
<div class="grid gap-6 md:grid-cols-2"><x-field name="name" label="Nom de l’option" :value="$variant->name" required placeholder="Option 1, cheveux longs…"/>
<x-field name="price" :label="'Prix ('.config('salon.currency').')'" :value="$variant->price" type="number" min="0" step="0.01" required/>
<x-field name="estimated_duration" label="Durée spécifique (minutes)" :value="$variant->estimated_duration" type="number" min="5" max="1440"/>
<x-field name="sort_order" label="Ordre d’affichage" :value="$variant->sort_order" type="number" min="0" max="9999" required/>
<div class="md:col-span-2"><x-field name="description" label="Description" :value="$variant->description" type="textarea"/></div><div class="md:col-span-2"><x-active-field :value="$variant->active"/></div></div>
<div class="mt-7 flex justify-end gap-3"><a class="btn-secondary" href="{{ route('services.show',$service) }}">Annuler</a><button class="btn">Enregistrer le tarif</button></div></form></x-layout>
