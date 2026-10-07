<x-layout title="Compte utilisateur"><x-page-heading :title="$user->exists ? 'Modifier la fiche' : 'Nouvelle fiche'" description="Donnez à chaque personne les accès adaptés à son rôle."><a class="btn-secondary" href="{{ route('users.index') }}">Retour à la liste</a></x-page-heading>
<form method="POST" action="{{ $user->exists ? route('users.update',$user) : route('users.store') }}" enctype="multipart/form-data" class="panel max-w-4xl p-6 md:p-8">@csrf @if($user->exists) @method('PUT') @endif
<div class="mb-7 border-b border-stone-100 pb-5"><h2 class="serif text-xl">Compte utilisateur</h2><p class="mt-1 text-xs text-muted">Les champs marqués d’un astérisque sont obligatoires.</p></div>
<div class="grid gap-6 md:grid-cols-2">
<x-field name="name" label="Nom complet" :value="$user->name" required/>
<x-field name="email" label="Adresse e-mail" :value="$user->email" type="email" required/>
<x-field name="password" label="Mot de passe" type="password" :required="!$user->exists" autocomplete="new-password" minlength="12"/>
<x-field name="password_confirmation" label="Confirmer le mot de passe" type="password" :required="!$user->exists" autocomplete="new-password"/>
<p class="text-xs text-muted md:col-span-2">12 caractères minimum, avec lettres et chiffres. Lors d’une modification, laissez vide pour conserver le mot de passe.</p>
<fieldset class="md:col-span-2"><legend class="mb-3 text-sm font-medium">Rôles *</legend><div class="grid gap-3 sm:grid-cols-2">@foreach($roles as $role)<label class="flex items-center gap-3 rounded-xl border border-stone-200 p-4"><input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id,old('roles',$user->exists ? $user->roles->modelKeys():[])))>{{ $role->name }}</label>@endforeach</div></fieldset>
</div>
<div class="mt-8 flex flex-wrap justify-end gap-3 border-t border-stone-100 pt-6"><a href="{{ route('users.index') }}" class="btn-secondary">Annuler</a><button class="btn">Enregistrer</button></div></form></x-layout>