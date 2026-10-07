<x-public-layout title="Votre parenthèse beauté">
    <section class="mx-auto grid max-w-7xl items-center gap-12 px-5 py-14 md:px-8 md:py-20 lg:grid-cols-[1.1fr_1fr]">
        <div>
            <p class="eyebrow !tracking-[.25em]">L’art de prendre soin de vous</p>
            <h1 class="serif mt-6 text-5xl leading-[1.12] tracking-tight sm:text-6xl lg:text-7xl">Votre beauté.<br>Votre moment.<br><em class="text-wine">Notre attention.</em></h1>
            <p class="mt-7 max-w-lg text-base leading-8 text-muted">Accordez-vous une parenthèse. Découvrez nos prestations et choisissez le soin qui vous ressemble, à votre rythme.</p>
            <div class="mt-8 flex flex-wrap gap-3"><a href="{{ route('booking.create') }}" class="btn min-h-12">Je réserve mon moment<x-icon name="arrow"/></a><a href="#prestations" class="btn-secondary min-h-12">Explorer les prestations</a></div>
            <p class="mt-5 flex items-center gap-2 text-xs text-muted"><x-icon name="calendar" class="size-4"/>Demande en ligne · Confirmation par le salon</p>
        </div>
        <div class="relative mx-auto w-full max-w-lg" aria-hidden="true">
            <div class="relative flex aspect-[4/4.3] items-center justify-center overflow-hidden rounded-t-full rounded-b-[2rem] bg-[#452332]">
                <div class="absolute inset-5 rounded-t-full rounded-b-3xl border border-white/20"></div>
                <div class="absolute -left-16 bottom-0 size-72 rounded-full bg-[#a75b76]/20"></div>
                <div class="absolute -right-12 top-14 size-56 rounded-full bg-[#a75b76]/20"></div>
                <div class="relative flex w-full flex-col items-center gap-5 px-8 text-white"><x-brand-logo class="w-full max-w-80 bg-transparent"/><p class="serif text-center text-3xl italic">La beauté commence<br>par un instant à soi.</p></div>
            </div>
            <div class="absolute -bottom-5 left-5 right-5 flex items-center gap-4 rounded-2xl border border-white bg-white p-5 shadow-sm"><span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-[#f5eaed] text-wine"><x-icon name="sparkles"/></span><div><p class="text-sm font-semibold">Une attention sur mesure</p><p class="mt-1 text-xs text-muted">Le soin, le style et le plaisir de se retrouver.</p></div></div>
        </div>
    </section>
    <section id="prestations" class="mx-auto max-w-7xl scroll-mt-6 px-5 py-12 md:px-8">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-5"><div><p class="eyebrow">La carte du salon</p><h2 class="serif mt-3 text-3xl sm:text-4xl">À chaque envie, son soin.</h2></div><p class="max-w-sm text-sm leading-6 text-muted">Explorez nos tarifs et choisissez votre prestation. Le salon validera ensuite votre horaire.</p></div>
        <div class="mb-8 flex flex-wrap gap-2" aria-label="Catégories de prestations"><a href="{{ route('home') }}#prestations" class="{{ empty($filters['category']) ? 'btn' : 'btn-secondary' }}">Tout découvrir</a>@foreach($categories as $category)<a href="{{ route('home', ['category' => $category->id]) }}#prestations" class="{{ ($filters['category'] ?? null) == $category->id ? 'btn' : 'btn-secondary' }}">{{ $category->name }}</a>@endforeach</div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($services as $service)
                @php($minimumPrice = collect([$service->base_price, ...$service->variants->pluck('price')])->filter(fn ($price) => $price !== null)->min())
                <article class="panel flex flex-col overflow-hidden">
                    @if($service->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($service->image_path) }}" alt="{{ $service->name }}" loading="lazy" class="h-44 w-full object-cover">@endif
                    <div class="flex flex-1 flex-col p-6"><p class="eyebrow">{{ $service->category->name }}</p><h3 class="serif mt-3 text-2xl">{{ $service->name }}</h3><p class="mt-3 line-clamp-3 text-sm leading-6 text-muted">{{ $service->description ?: 'Choisissez votre formule et laissez-nous prendre soin de vous.' }}</p>
                        <div class="mt-auto flex flex-wrap items-end justify-between gap-3 pt-7"><div><p class="text-xs text-muted">À partir de</p><p class="mt-1 text-xl font-semibold">{{ $minimumPrice }} <span class="text-xs font-normal">{{ config('salon.currency') }}</span></p></div><a href="{{ route('booking.create', ['service' => $service->id]) }}" class="btn-secondary" aria-label="Réserver : {{ $service->name }}">Réserver<x-icon name="arrow" class="size-4"/></a></div>
                    </div>
                </article>
            @empty
                <div class="panel col-span-full p-8 text-center"><p class="serif text-2xl">Notre carte se prépare.</p><p class="mt-3 text-sm text-muted">Aucune prestation réservable dans cette sélection pour le moment.</p></div>
            @endforelse
        </div>
        <div class="mt-7">{{ $services->links() }}</div>
    </section>
    <section class="mx-auto max-w-7xl px-5 py-12 md:px-8">
        <div class="rounded-3xl bg-[#f0e3e2] p-7 md:p-12"><p class="eyebrow">En toute simplicité</p><h2 class="serif mt-3 text-3xl sm:text-4xl">Votre prochain rendez-vous, en trois étapes.</h2><div class="mt-9 grid gap-8 md:grid-cols-3">
            @foreach([['Choisissez votre soin', 'Sélectionnez une prestation et le tarif adapté à votre envie.'], ['Proposez un horaire', 'Indiquez vos coordonnées et le moment qui vous conviendrait.'], ['Recevez la confirmation', 'Le salon vérifie la disponibilité et vous contacte pour confirmer le rendez-vous.']] as [$heading, $description])
                <div><span class="serif text-3xl text-wine">0{{ $loop->iteration }}</span><h3 class="mt-3 font-semibold">{{ $heading }}</h3><p class="mt-2 text-sm leading-7 text-muted">{{ $description }}</p></div>
            @endforeach
        </div></div>
    </section>
</x-public-layout>
