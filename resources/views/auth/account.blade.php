<x-layout title="Mon compte"><x-page-heading title="Mon compte" description="Protégez votre accès personnel au salon."/>
<form action="{{ route('password.update') }}" method="POST" class="panel max-w-xl space-y-5 p-7">@csrf @method('PUT')
<p class="text-sm text-muted">{{ auth()->user()->name }} · {{ auth()->user()->email }}</p>
<x-field name="current_password" label="Mot de passe actuel" type="password" required autocomplete="current-password"/>
<x-field name="password" label="Nouveau mot de passe" type="password" required autocomplete="new-password" minlength="12"/>
<p class="text-xs text-muted">Au moins 12 caractères, avec des lettres et des chiffres.</p>
<x-field name="password_confirmation" label="Confirmer le mot de passe" type="password" required autocomplete="new-password"/>
<button class="btn">Modifier le mot de passe</button></form></x-layout>
