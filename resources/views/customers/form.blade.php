<x-layout title="Client"><x-page-heading :title="$customer->exists ? 'Modifier la fiche' : 'Nouvelle fiche'" description="Les petites attentions commencent par une fiche bien renseignée."><a class="btn-secondary" href="{{ route('customers.index') }}">Retour à la liste</a></x-page-heading>
<form method="POST" action="{{ $customer->exists ? route('customers.update',$customer) : route('customers.store') }}" enctype="multipart/form-data" class="panel max-w-4xl p-6 md:p-8">@csrf @if($customer->exists) @method('PUT') @endif
<div class="mb-7 border-b border-stone-100 pb-5"><h2 class="serif text-xl">Client</h2><p class="mt-1 text-xs text-muted">Les champs marqués d’un astérisque sont obligatoires.</p></div>
<div class="grid gap-6 md:grid-cols-2">
<x-field name="first_name" label="Prénom" :value="$customer->first_name" required maxlength="150"/>
<x-field name="last_name" label="Nom" :value="$customer->last_name" maxlength="100"/>
<x-field name="phone" label="Téléphone principal" :value="$customer->phone" required type="tel" placeholder="+243…"/>
<x-field name="secondary_phone" label="Autre téléphone" :value="$customer->secondary_phone" type="tel"/>
<x-field name="email" label="Adresse e-mail" :value="$customer->email" type="email"/>
<x-field name="birth_date" label="Date de naissance" :value="$customer->birth_date?->format('Y-m-d')" type="date"/>
<div class="field"><label for="gender">Genre</label><select id="gender" name="gender"><option value="">Non renseigné</option>@foreach(['female'=>'Femme','male'=>'Homme','other'=>'Autre'] as $key=>$label)<option value="{{ $key }}" @selected(old('gender',$customer->gender)===$key)>{{ $label }}</option>@endforeach</select></div>
<div class="field"><label for="preferred_beautician_id">Membre de l’équipe préféré</label><select name="preferred_beautician_id" id="preferred_beautician_id"><option value="">Sans préférence</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(old('preferred_beautician_id',$customer->preferred_beautician_id)==$employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
<div class="md:col-span-2"><x-field name="address" label="Adresse" :value="$customer->address" type="textarea"/></div>
<div class="md:col-span-2"><x-field name="allergies" label="Allergies et sensibilités" :value="$customer->allergies" type="textarea" placeholder="Précautions utiles pour les soins…"/></div>
<div class="md:col-span-2"><x-field name="notes" label="Notes et préférences" :value="$customer->notes" type="textarea"/></div>
</div>
<div class="mt-8 flex flex-wrap justify-end gap-3 border-t border-stone-100 pt-6"><a href="{{ route('customers.index') }}" class="btn-secondary">Annuler</a><button class="btn">Enregistrer</button></div></form></x-layout>
