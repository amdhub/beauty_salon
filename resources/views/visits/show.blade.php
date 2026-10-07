<x-layout title="Détail de vente">
<x-page-heading :title="$visit->number" :description="$visit->customer_name_snapshot.' · '.$visit->created_at->format('d/m/Y H:i')"><a href="{{ route('visits.index') }}" class="btn-secondary">Ventes & reçus</a><a href="{{ route('visits.receipt.thermal', ['visit' => $visit, 'print' => 1]) }}" class="btn" target="_blank" rel="noopener"><x-icon name="receipt"/>Ticket 80 mm</a></x-page-heading>
<div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_340px]"><x-visit-receipt :visit="$visit"/>
<div class="space-y-5">
@can('recordPayment',$visit)
@if($visit->balance_minor>0)
<form method="POST" action="{{ route('visits.payments.store',$visit) }}" class="panel space-y-5 p-6">@csrf<input type="hidden" name="idempotency_key" value="{{ old('idempotency_key',$paymentKey) }}"/>
<div><h2 class="serif text-xl">Régler le solde</h2><p class="mt-2 text-sm text-muted">Restant : <strong>{{ \App\Services\Money::decimal($visit->balance_minor) }} {{ $visit->currency }}</strong></p></div>
<x-field name="amount" :label="'Montant reçu ('.$visit->currency.')'" :value="\App\Services\Money::decimal($visit->balance_minor)" type="number" min="0.01" step="0.01" required/>
<div class="field"><label for="payment_method">Mode de paiement</label><select id="payment_method" name="payment_method" required>@foreach(\App\Enums\PaymentMethod::cases() as $method)<option value="{{ $method->value }}" @selected(old('payment_method')===$method->value)>{{ $method->label() }}</option>@endforeach</select></div>
<x-field name="reference" label="Référence (facultatif)" maxlength="255"/>
<button type="submit" class="btn min-h-12 w-full">Enregistrer le paiement</button>
</form>
@else<div class="panel p-6"><span class="badge"><x-icon name="check" class="size-4"/>Vente entièrement payée</span><p class="mt-3 text-sm leading-6 text-muted">Le reçu reste disponible pour impression ou réimpression.</p></div>@endif
@endcan
@if($visit->notes)<section class="panel p-6"><h2 class="serif text-xl">Note interne</h2><p class="mt-3 whitespace-pre-line text-sm text-muted">{{ $visit->notes }}</p></section>@endif
@can('create',\App\Models\SalonVisit::class)<a href="{{ route('pos.index') }}" class="btn-secondary w-full"><x-icon name="plus"/>Nouvelle vente</a>@endcan
</div></div></x-layout>
