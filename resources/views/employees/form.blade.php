<x-layout title="Membre de l’équipe"><x-page-heading :title="$employee->exists ? 'Modifier la fiche' : 'Nouvelle fiche'" description="Rassemblez les talents et identifiez les spécialités de chacun."><a class="btn-secondary" href="{{ route('employees.index') }}">Retour à la liste</a></x-page-heading>
<form method="POST" action="{{ $employee->exists ? route('employees.update',$employee) : route('employees.store') }}" enctype="multipart/form-data" class="panel max-w-4xl p-6 md:p-8">@csrf @if($employee->exists) @method('PUT') @endif
<div class="mb-7 border-b border-stone-100 pb-5"><h2 class="serif text-xl">Membre de l’équipe</h2><p class="mt-1 text-xs text-muted">Les champs marqués d’un astérisque sont obligatoires.</p></div>
<div class="grid gap-6 md:grid-cols-2">
<x-field name="name" label="Nom complet" :value="$employee->name" required/>
<x-field name="position" label="Fonction" :value="$employee->position" required placeholder="Coiffeuse, esthéticienne…"/>
<x-field name="phone" label="Téléphone" :value="$employee->phone" type="tel"/>
<x-field name="email" label="Adresse e-mail" :value="$employee->email" type="email"/>
<x-field name="hire_date" label="Date d’entrée" :value="$employee->hire_date?->format('Y-m-d')" type="date"/>
<div class="field"><label for="user_id">Compte utilisateur associé</label><select name="user_id" id="user_id"><option value="">Aucun compte associé</option>@foreach($users as $account)<option value="{{ $account->id }}" @selected(old('user_id',$employee->user_id)==$account->id)>{{ $account->name }}</option>@endforeach</select></div>
<fieldset class="md:col-span-2"><legend class="mb-3 text-sm font-medium">Spécialités</legend><div class="flex flex-wrap gap-3">@foreach($categories as $category)<label class="flex items-center gap-2 rounded-xl border border-stone-200 px-3 py-2"><input type="checkbox" name="specialties[]" value="{{ $category->id }}" @checked(in_array($category->id,old('specialties',$employee->exists ? $employee->specialties->modelKeys():[])))>{{ $category->name }}</label>@endforeach</div></fieldset>
<div class="md:col-span-2"><x-field name="notes" label="Notes" :value="$employee->notes" type="textarea"/></div><div class="md:col-span-2"><x-active-field :value="$employee->active"/></div>
</div>
<div class="mt-8 flex flex-wrap justify-end gap-3 border-t border-stone-100 pt-6"><a href="{{ route('employees.index') }}" class="btn-secondary">Annuler</a><button class="btn">Enregistrer</button></div></form></x-layout>