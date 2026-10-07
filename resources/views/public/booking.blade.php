<x-public-layout title="Demander un rendez-vous">
    <div class="mx-auto max-w-6xl px-5 py-12 md:px-8">
        <p class="eyebrow">Un moment pour vous</p><h1 class="serif mt-3 text-4xl sm:text-5xl">Préparons votre rendez-vous.</h1><p class="mt-4 max-w-2xl text-sm leading-7 text-muted">Choisissez votre prestation et proposez un horaire. Votre réservation sera confirmée manuellement par le salon, selon ses disponibilités.</p>
        @if($errors->any())<div role="alert" class="mt-6 rounded-xl border border-rose-200 bg-rose-50 p-5 text-sm text-rose-900"><p class="font-semibold">Vérifiez votre demande :</p><ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="mt-8 grid items-start gap-7 lg:grid-cols-[minmax(0,1fr)_320px]">
            @if($services->isNotEmpty())
                <form action="{{ route('booking.store') }}" method="POST" data-booking-form class="panel min-w-0 p-5 sm:p-8">
                    @csrf<input type="hidden" name="request_key" value="{{ $requestKey }}">
                    <h2 class="serif mb-6 text-2xl">Votre demande</h2>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="field min-w-0 sm:col-span-2"><label for="service_option">Prestation et tarif *</label><select id="service_option" name="service_option" required class="min-w-0"><option value="">Choisissez votre prestation</option>
                            @foreach($services as $service)
                                <optgroup label="{{ $service->name }}">
                                    @if($service->base_price !== null)<option data-label="{{ $service->name }} · Tarif de base" data-price="{{ $service->base_price }} {{ config('salon.currency') }}" data-duration="{{ $service->default_duration }} min" value="{{ $service->id }}:base" @selected(old('service_option') === $service->id.':base' || (!old('service_option') && $selectedService == $service->id))>{{ $service->name }} · Tarif de base — {{ $service->base_price }} {{ config('salon.currency') }} · {{ $service->default_duration }} min</option>@endif
                                    @foreach($service->variants as $variant)<option data-label="{{ $service->name }} · {{ $variant->name }}" data-price="{{ $variant->price }} {{ config('salon.currency') }}" data-duration="{{ $variant->estimated_duration ?? $service->default_duration }} min" value="{{ $service->id }}:{{ $variant->id }}" @selected(old('service_option') === $service->id.':'.$variant->id || (!old('service_option') && $selectedService == $service->id && $service->base_price === null && $loop->first))>{{ $service->name }} · {{ $variant->name }} — {{ $variant->price }} {{ config('salon.currency') }} · {{ $variant->estimated_duration ?? $service->default_duration }} min</option>@endforeach
                                </optgroup>
                            @endforeach
                        </select></div>
                        <div data-booking-summary hidden class="rounded-xl bg-[#f8f2f3] p-4 text-sm sm:col-span-2"><p data-booking-label class="font-semibold"></p><div class="mt-3 flex flex-wrap justify-between gap-3"><p>Tarif : <strong data-booking-price></strong></p><p class="text-muted">Durée estimée : <span data-booking-duration></span></p></div></div>
                        <div class="sm:col-span-2"><x-field name="preferred_at" label="Date et heure souhaitées" type="datetime-local" :min="now()->format('Y-m-d\TH:i')" :max="now()->addDays(90)->format('Y-m-d\TH:i')" required/><p class="mt-2 text-xs text-muted">Heure locale du salon. Ce choix ne garantit pas la disponibilité.</p></div>
                        <x-field name="customer_name" label="Votre nom" autocomplete="name" required maxlength="150"/>
                        <x-field name="phone" label="Téléphone" type="tel" autocomplete="tel" required maxlength="25" placeholder="+243…"/>
                        <div class="sm:col-span-2"><x-field name="email" label="E-mail (facultatif)" type="email" autocomplete="email" maxlength="255"/></div>
                        <div class="sm:col-span-2"><x-field name="customer_notes" label="Une précision pour votre rendez-vous ?" type="textarea" maxlength="2000" placeholder="Une préférence ou une information utile pour votre prestation…"/></div>
                    </div>
                    <p class="mt-6 text-xs leading-6 text-muted">Vos coordonnées servent à traiter cette demande et à vous contacter au sujet du rendez-vous.</p>
                    @if($whatsappEnabled)<p class="mt-3 text-xs leading-6 text-muted">Après l’enregistrement, WhatsApp s’ouvrira avec le récapitulatif destiné au responsable. Appuyez sur « Envoyer » dans WhatsApp pour lui transmettre votre demande.</p>@endif
                    <button class="btn mt-5 min-h-12 w-full">Envoyer ma demande<x-icon name="arrow"/></button>
                </form>
            @else
                <div class="panel p-8"><h2 class="serif text-2xl">Les réservations ouvriront bientôt.</h2><p class="mt-3 text-sm text-muted">Aucune prestation n’est disponible à la réservation pour le moment.</p><a href="{{ route('home') }}" class="btn-secondary mt-6">Retour à l’accueil</a></div>
            @endif
            <aside class="rounded-2xl bg-[#f0e3e2] p-7"><x-icon name="calendar" class="size-8 text-wine"/><h2 class="serif mt-5 text-2xl">Et ensuite ?</h2><p class="mt-4 text-sm leading-7 text-muted">Votre demande arrive directement au salon. L’équipe vérifie l’horaire et prend contact avec vous pour le confirmer ou vous proposer un autre moment.</p><div class="mt-6 border-t border-wine/15 pt-5"><p class="text-sm font-semibold">Aucun paiement en ligne</p><p class="mt-2 text-sm leading-6 text-muted">Vous réglez votre prestation au salon. Aucun compte client n’est nécessaire.</p></div></aside>
        </div>
    </div>
</x-public-layout>
