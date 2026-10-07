<x-layout title="Prestation"><x-page-heading :title="$service->exists ? 'Modifier la fiche' : 'Nouvelle fiche'" description="Un catalogue flexible, à l’image de votre savoir-faire."><a class="btn-secondary" href="{{ route('services.index') }}">Retour à la liste</a></x-page-heading>
<form method="POST" action="{{ $service->exists ? route('services.update',$service) : route('services.store') }}" enctype="multipart/form-data" class="panel max-w-4xl p-6 md:p-8">@csrf @if($service->exists) @method('PUT') @endif
<div class="mb-7 border-b border-stone-100 pb-5"><h2 class="serif text-xl">Prestation</h2><p class="mt-1 text-xs text-muted">Les champs marqués d’un astérisque sont obligatoires.</p></div>
<div class="grid gap-6 md:grid-cols-2">
<x-field name="name" label="Nom de la prestation" :value="$service->name" required maxlength="150"/>
<div class="field"><label for="service_category_id">Catégorie *</label><select id="service_category_id" name="service_category_id" required><option value="">Choisir une catégorie</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('service_category_id',$service->service_category_id)==$category->id)>{{ $category->name }}</option>@endforeach</select></div>
<x-field name="default_duration" label="Durée indicative (minutes)" :value="$service->default_duration" type="number" min="5" max="1440" required/>
<x-field name="base_price" :label="'Prix de base facultatif ('.config('salon.currency').')'" :value="$service->base_price" type="number" min="0" step="0.01"/>
<p class="text-xs leading-5 text-muted md:col-span-2">Ajoutez les variantes et leurs prix depuis la fiche de la prestation après enregistrement. Les durées initiales sont indicatives et peuvent être ajustées.</p>
<div class="md:col-span-2"><x-field name="description" label="Description" :value="$service->description" type="textarea"/></div>
<x-field name="image" label="Photo (JPG, PNG ou WebP · 2 Mo max.)" type="file" accept="image/jpeg,image/png,image/webp"/>
@if($service->image_path)<label class="flex items-center gap-2"><input type="checkbox" name="remove_image" value="1">Retirer la photo actuelle</label>@endif
<div class="md:col-span-2"><x-field name="notes" label="Notes internes" :value="$service->notes" type="textarea"/></div><div class="md:col-span-2"><x-active-field :value="$service->active"/></div>
</div>
<div class="mt-8 flex flex-wrap justify-end gap-3 border-t border-stone-100 pt-6"><a href="{{ route('services.index') }}" class="btn-secondary">Annuler</a><button class="btn">Enregistrer</button></div></form></x-layout>