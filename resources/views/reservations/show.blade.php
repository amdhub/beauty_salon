<x-layout title="Détail de réservation">
    <x-page-heading :title="$reservation->number" :description="'Demande reçue le '.$reservation->created_at->format('d/m/Y à H:i')"><a href="{{ route('reservations.index') }}" class="btn-secondary">Toutes les réservations</a></x-page-heading>
    <div class="grid items-start gap-6 lg:grid-cols-2">
        <section class="panel p-6 sm:p-8"><div class="flex items-center justify-between gap-3"><h2 class="serif text-2xl">{{ $reservation->customer_name }}</h2><span class="{{ $reservation->status === 'confirmed' ? 'badge' : 'badge-muted' }}">{{ $reservation->status_label }}</span></div>
            <div class="mt-5 flex flex-col gap-3 text-sm"><a href="tel:{{ $reservation->phone }}" class="link">{{ $reservation->phone }}</a>@if($reservation->email)<p class="break-all text-muted">{{ $reservation->email }}</p>@endif</div>
            @if($reservation->customer && ! $reservation->customer->trashed())
                @can('view', $reservation->customer)
                    <div class="mt-5"><a href="{{ route('customers.show', $reservation->customer) }}" class="btn-secondary">Voir la fiche client · {{ $reservation->customer->number }}</a></div>
                @endcan
            @endif
            <dl class="mt-7 space-y-5 border-t border-stone-100 pt-6 text-sm"><div><dt class="eyebrow">Prestation demandée</dt><dd class="mt-2 font-semibold">{{ $reservation->service_name_snapshot }} @if($reservation->variant_name_snapshot) · {{ $reservation->variant_name_snapshot }} @endif</dd></div><div><dt class="eyebrow">Tarif / durée</dt><dd class="mt-2">{{ $reservation->price_snapshot }} {{ $reservation->currency }} · {{ $reservation->duration_minutes }} min</dd></div><div><dt class="eyebrow">Horaire souhaité par le client</dt><dd class="mt-2">{{ $reservation->preferred_at->format('d/m/Y à H:i') }}</dd></div>@if($reservation->scheduled_at)<div><dt class="eyebrow">Horaire retenu</dt><dd class="mt-2 font-semibold text-wine">{{ $reservation->scheduled_at->format('d/m/Y à H:i') }}</dd></div>@endif</dl>
            @if($reservation->customer_notes)<div class="mt-6 rounded-xl bg-stone-50 p-4"><h3 class="text-sm font-semibold">Message du client</h3><p class="mt-2 whitespace-pre-line break-words text-sm text-muted">{{ $reservation->customer_notes }}</p></div>@endif
            @if($reservation->internal_notes)<div class="mt-6 border-t border-stone-100 pt-5"><h3 class="text-sm font-semibold">Notes internes</h3><p class="mt-2 whitespace-pre-line break-words text-sm text-muted">{{ $reservation->internal_notes }}</p></div>@endif
            @if($reservation->processed_by_name)<p class="mt-6 text-xs text-muted">Dernier traitement : {{ $reservation->processed_by_name }} · {{ $reservation->updated_at->format('d/m/Y H:i') }}</p>@endif
        </section>
        @can('update', $reservation)
            @if(\App\Models\Reservation::TRANSITIONS[$reservation->status])
                <form action="{{ route('reservations.update', $reservation) }}" method="POST" class="panel p-6 sm:p-8" x-data="{ status: @js(old('status', 'confirmed')) }">
                    @csrf @method('PUT')<input type="hidden" name="expected_status" value="{{ $reservation->status }}">
                    <h2 class="serif text-2xl">Traiter la demande</h2><p class="mt-3 text-sm leading-6 text-muted">Vérifiez la disponibilité de l’équipe et contactez le client avant de confirmer ou de modifier son horaire.</p>
                    <p class="mt-3 text-sm text-muted">La confirmation enregistre automatiquement le client. Une fiche existante avec le même téléphone est réutilisée, ou réactivée si elle était archivée.</p>
                    <div class="field mt-6"><label for="status">Décision *</label><select id="status" name="status" x-model="status" required>@foreach(\App\Models\Reservation::TRANSITIONS[$reservation->status] as $status)<option value="{{ $status }}" @selected(old('status', 'confirmed') === $status)>{{ \App\Models\Reservation::STATUSES[$status] }}</option>@endforeach</select></div>
                    <div class="mt-5" x-show="status === 'confirmed'"><x-field name="scheduled_at" label="Date et heure à confirmer" type="datetime-local" :value="($reservation->scheduled_at ?? $reservation->preferred_at)->format('Y-m-d\TH:i')" x-bind:required="status === 'confirmed'" x-bind:disabled="status !== 'confirmed'"/><p class="mt-2 text-xs text-muted">Heure locale : {{ config('app.timezone') }}.</p></div>
                    <div class="mt-5"><x-field name="internal_notes" label="Notes internes" type="textarea" :value="$reservation->internal_notes" maxlength="3000" placeholder="Client contacté, nouvel horaire convenu, motif du refus…"/></div>
                    <button class="btn mt-6 w-full">Enregistrer la décision</button><p class="mt-3 text-xs leading-5 text-muted">La mise à jour n’envoie pas de SMS ni d’e-mail. Informez le client directement.</p>
                </form>
            @else
                <aside class="panel p-6"><h2 class="serif text-2xl">Demande clôturée</h2><p class="mt-3 text-sm text-muted">Cette réservation est {{ mb_strtolower($reservation->status_label) }}. Son historique reste consultable.</p></aside>
            @endif
        @endcan
    </div>
</x-layout>
