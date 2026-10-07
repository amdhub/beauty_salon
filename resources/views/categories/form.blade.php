<x-layout title="Catégorie"><x-page-heading :title="$category->exists ? 'Modifier la fiche' : 'Nouvelle fiche'" description="Organisez vos prestations en univers, librement."><a class="btn-secondary" href="{{ route('categories.index') }}">Retour à la liste</a></x-page-heading>
<form method="POST" action="{{ $category->exists ? route('categories.update',$category) : route('categories.store') }}" enctype="multipart/form-data" class="panel max-w-4xl p-6 md:p-8">@csrf @if($category->exists) @method('PUT') @endif
<div class="mb-7 border-b border-stone-100 pb-5"><h2 class="serif text-xl">Catégorie</h2><p class="mt-1 text-xs text-muted">Les champs marqués d’un astérisque sont obligatoires.</p></div>
<div class="grid gap-6 md:grid-cols-2">
<x-field name="name" label="Nom de la catégorie" :value="$category->name" required maxlength="100"/>
<x-field name="sort_order" label="Ordre d’affichage" :value="$category->sort_order" type="number" min="0" max="9999" required/>
<div class="md:col-span-2"><x-field name="description" label="Description" :value="$category->description" type="textarea"/></div>
<div class="md:col-span-2"><x-active-field :value="$category->active"/></div>
</div>
<div class="mt-8 flex flex-wrap justify-end gap-3 border-t border-stone-100 pt-6"><a href="{{ route('categories.index') }}" class="btn-secondary">Annuler</a><button class="btn">Enregistrer</button></div></form></x-layout>