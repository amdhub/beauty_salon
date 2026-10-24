<x-layout title="Enregistrer une dépense">
    <x-page-heading title="Enregistrer une dépense" description="Saisissez une somme effectivement réglée par le salon."><a href="{{ route('expenses.index') }}" class="btn-secondary">Dépenses</a></x-page-heading>
    <form method="POST" action="{{ route('expenses.store') }}" class="panel max-w-3xl space-y-6 p-6 sm:p-8">
        @csrf<input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $submissionKey) }}">
        <x-field name="label" label="Libellé / motif" placeholder="Loyer du mois, achat de produits…" maxlength="255" required/>
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="field"><label for="category">Catégorie *</label><select id="category" name="category" required><option value="">Choisir une catégorie</option>@foreach(\App\Models\Expense::CATEGORIES as $key => $label)<option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>@endforeach</select></div>
            <x-field name="amount" :label="'Montant payé ('.config('salon.currency').')'" type="number" min="0.01" max="9999999999.99" step="0.01" required/>
            <x-field name="paid_on" label="Date du règlement" type="date" :value="today()->format('Y-m-d')" :max="today()->format('Y-m-d')" required/>
            <div class="field"><label for="payment_method">Mode de paiement *</label><select id="payment_method" name="payment_method" required>@foreach(\App\Enums\PaymentMethod::cases() as $method)<option value="{{ $method->value }}" @selected(old('payment_method') === $method->value)>{{ $method->label() }}</option>@endforeach</select></div>
        </div>
        <x-field name="payee" label="Bénéficiaire / fournisseur (facultatif)" maxlength="255"/>
        <x-field name="reference" label="Référence du paiement ou du justificatif (facultatif)" maxlength="255"/>
        <x-field name="notes" label="Notes (facultatif)" type="textarea" maxlength="3000"/>
        <p class="text-xs leading-6 text-muted">Pour un approvisionnement, enregistrez ici le montant réellement payé et sa référence. Les prêts à l’équipe et leurs remboursements sont déjà comptabilisés depuis le module Dettes.</p>
        <div class="flex flex-wrap justify-end gap-3"><a href="{{ route('expenses.index') }}" class="btn-secondary">Annuler</a><button class="btn">Enregistrer la dépense</button></div>
    </form>
</x-layout>
