<x-public-layout title="Votre demande de réservation">
    <section class="mx-auto max-w-2xl px-5 py-14">
        <div class="panel p-6 text-center sm:p-10"><span class="mx-auto flex size-16 items-center justify-center rounded-full bg-[#f5eaed] text-wine"><x-icon name="check" class="size-8"/></span><h1 class="serif mt-6 text-3xl">Votre demande a bien été reçue.</h1><p class="mt-3 text-sm text-muted">Référence {{ $reservation->number }} · {{ $reservation->status_label }}</p>
            <dl class="mt-8 space-y-4 rounded-2xl bg-stone-50 p-5 text-left text-sm"><div><dt class="text-xs text-muted">Prestation</dt><dd class="mt-1 font-semibold">{{ $reservation->service_name_snapshot }} @if($reservation->variant_name_snapshot) · {{ $reservation->variant_name_snapshot }} @endif</dd></div><div><dt class="text-xs text-muted">Horaire souhaité</dt><dd class="mt-1">{{ $reservation->preferred_at->format('d/m/Y à H:i') }}</dd></div>@if($reservation->scheduled_at)<div><dt class="text-xs text-muted">Horaire retenu par le salon</dt><dd class="mt-1 font-semibold">{{ $reservation->scheduled_at->format('d/m/Y à H:i') }}</dd></div>@endif<div><dt class="text-xs text-muted">Tarif de la prestation</dt><dd class="mt-1">{{ $reservation->price_snapshot }} {{ $reservation->currency }} · {{ $reservation->duration_minutes }} min</dd></div></dl>
            @if($reservation->status === 'pending')<p class="mt-6 text-sm leading-7 text-muted">Votre rendez-vous n’est pas encore confirmé. Le salon vous contactera au numéro renseigné pour valider la disponibilité.</p>@endif
            @if($whatsappUrl)
                <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-900">
                    <p class="font-semibold">Transmettez votre récapitulatif au responsable</p>
                    @if($autoOpenWhatsApp)<p class="mt-2" role="status">Ouverture de WhatsApp… Si l’application ne s’ouvre pas, utilisez le bouton ci-dessous.</p>@endif
                    <p class="mt-2 leading-6">Le message est prérempli avec votre réservation. Appuyez sur « Envoyer » dans WhatsApp. Votre demande est déjà enregistrée au salon.</p>
                    <a href="{{ $whatsappUrl }}" @if($autoOpenWhatsApp) data-whatsapp-auto-open @endif rel="noreferrer" class="btn mt-4"><x-icon name="phone"/>Ouvrir WhatsApp</a>
                </div>
            @endif
            <div class="mt-7 flex flex-wrap justify-center gap-3"><a href="{{ route('home') }}" class="btn-secondary">Retour à l’accueil</a><a href="{{ route('booking.create') }}" class="btn">Une autre réservation</a></div>
        </div>
    </section>
</x-public-layout>
