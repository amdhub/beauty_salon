@props(['title' => null])
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Découvrez les prestations de {{ config('salon.name') }} et demandez votre rendez-vous en ligne. Confirmation par le salon.">
    @if(!request()->routeIs('home'))<meta name="robots" content="noindex, nofollow">@endif
    <title>{{ $title ? $title.' · ' : '' }}{{ config('salon.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/nail-master-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-dvh flex-col">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:p-4">Aller au contenu</a>
    <header class="border-b border-stone-200/70 bg-white/80">
        <nav class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 md:px-8" aria-label="Navigation du salon">
            <a href="{{ route('home') }}" class="shrink-0"><x-brand-logo class="w-20 sm:w-24"/></a>
            <div class="flex min-w-0 flex-1 flex-wrap items-center justify-end gap-x-3 gap-y-2 text-xs sm:gap-6 sm:text-sm">
                <a href="{{ route('home') }}#prestations" class="text-muted hover:text-wine">Nos prestations</a>
                <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="text-muted hover:text-wine">Espace équipe</a>
                <a href="{{ route('booking.create') }}" class="btn">Réserver<x-icon name="arrow" class="size-4"/></a>
            </div>
        </nav>
    </header>
    <main id="contenu" class="min-w-0 flex-1">{{ $slot }}</main>
    <footer class="mt-12 border-t border-stone-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col justify-between gap-4 px-5 py-8 text-sm sm:flex-row sm:items-center md:px-8"><div><x-brand-logo class="mb-3 w-28"/><p class="mt-1 text-xs text-muted">Un moment pour vous, une attention à chaque détail.</p></div><div class="flex flex-wrap gap-5 text-xs text-muted"><a href="{{ route('booking.create') }}" class="hover:text-wine">Demander un rendez-vous</a><a href="{{ route('login') }}" class="hover:text-wine">Connexion équipe</a><span>© {{ now()->year }}</span></div></div>
    </footer>
</body>
</html>
