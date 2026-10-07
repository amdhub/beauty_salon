@props(['title'=>'Tableau de bord'])
<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ $title }} · {{ config('salon.name') }}</title><link rel="icon" type="image/png" href="{{ asset('images/nail-master-logo.png') }}">@vite(['resources/css/app.css','resources/js/app.js'])@livewireStyles</head>
<body x-data="{ navigationOpen: false }">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:z-50 focus:bg-white focus:p-4">Aller au contenu</a>
<div x-cloak x-show="navigationOpen" @click="navigationOpen=false" class="fixed inset-0 z-30 bg-black/30 lg:hidden"></div>
<aside class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-stone-200/70 bg-white transition-transform lg:translate-x-0" :class="navigationOpen ? 'translate-x-0' : '-translate-x-full'">
 <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-3 px-5 py-3"><x-brand-logo class="w-20 shrink-0"/><span class="min-w-0"><span class="serif block text-lg leading-tight">{{ config('salon.name') }}</span><span class="mt-1 block text-[10px] text-muted">Espace salon</span></span></a>
 <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-5" aria-label="Navigation principale">
 <p class="eyebrow px-4 pb-3">Votre espace</p>
 <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm text-muted hover:bg-stone-50"><x-icon name="flower"/>Site public</a>
 @php($navigation = [
 ['dashboard','Vue d’ensemble','grid',null,null],
 ['pos.index','Caisse / POS','cart','create',\App\Models\SalonVisit::class],
 ['visits.index','Ventes & reçus','receipt','viewAny',\App\Models\SalonVisit::class],
 ['reservations.index','Réservations','calendar','viewAny',\App\Models\Reservation::class],
 ['products.index','Produits & stocks','layers','viewAny',\App\Models\ConsumableProduct::class],
 ['customers.index','Clients','users','viewAny',\App\Models\Customer::class],
 ['employees.index','Équipe','flower','viewAny',\App\Models\Employee::class],
 ['services.index','Prestations & tarifs','scissors','viewAny',\App\Models\Service::class],
 ['categories.index','Catégories','layers','viewAny',\App\Models\ServiceCategory::class],
 ['users.index','Utilisateurs','shield','viewAny',\App\Models\User::class],
 ['roles.index','Rôles & permissions','shield','viewAny',\App\Models\Role::class],
 ['settings.edit','Paramètres','settings','viewAny',\App\Models\SalonSetting::class],
 ])
 @foreach($navigation as [$route,$label,$icon,$ability,$model])
 @if(!$ability || auth()->user()->can($ability,$model))
 <a href="{{ route($route) }}" @class(['flex items-center gap-3 rounded-xl px-4 py-3 text-sm transition','bg-[#f5eaed] font-semibold text-wine'=>request()->routeIs(explode('.',$route)[0].'*'),'text-muted hover:bg-stone-50 hover:text-ink'=>!request()->routeIs(explode('.',$route)[0].'*')])><x-icon :name="$icon"/>{{ $label }}</a>
 @endif
 @endforeach
 </nav>
 <div class="mx-5 mb-5 rounded-2xl bg-[#f8f3ee] p-4"><x-icon name="sparkles" class="mb-2 size-5 text-wine"/><p class="serif text-lg">Chaque détail compte.</p><p class="mt-1 text-xs leading-5 text-muted">Un espace pensé pour votre salon et celles et ceux qui lui donnent vie.</p></div>
</aside>
<div class="flex min-h-dvh flex-col lg:ml-64">
<header class="flex min-h-20 items-center justify-between gap-2 border-b border-stone-200/70 bg-white px-3 py-3 sm:gap-4 sm:px-5 md:px-9">
 <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
  <button @click="navigationOpen=!navigationOpen" :aria-expanded="navigationOpen" aria-label="Ouvrir le menu" class="flex size-11 shrink-0 items-center justify-center rounded-xl hover:bg-stone-50 lg:hidden"><x-icon name="menu"/></button>
  <p class="min-w-0 truncate text-sm text-muted"><span class="hidden xl:inline">Espace salon <span class="mx-3 text-stone-300">/</span></span><span class="font-medium text-ink">{{ $title }}</span></p>
 </div>
 <div class="flex shrink-0 items-center gap-4">
  <div class="hidden text-right 2xl:block"><p class="text-xs capitalize text-muted">{{ now()->locale('fr')->translatedFormat('l d F Y') }}</p><p class="mt-1 text-[11px] text-muted">{{ config('salon.currency') }}</p></div>
  <x-topbar-account/>
 </div>
</header>
<main id="main" class="mx-auto w-full min-w-0 max-w-[1600px] flex-1 p-5 md:p-9">
 @if(session('success'))<div role="status" class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900"><x-icon name="check"/>{{ session('success') }}</div>@endif
 @if($errors->any())<div role="alert" class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><p class="font-semibold">Vérifiez les informations saisies.</p><ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
 {{ $slot }}
</main>
<footer class="mx-auto w-full max-w-[1600px] shrink-0 px-5 pb-5 md:px-9 md:pb-6">
 <div class="flex flex-col gap-2 border-t border-stone-200/70 pt-5 text-center text-[11px] leading-5 text-muted sm:flex-row sm:items-center sm:justify-between sm:text-left">
  <span>{{ config('salon.name') }} · Votre quotidien, en toute sérénité.</span>
  <span>Gestion du salon</span>
 </div>
</footer>
</div>@livewireScripts</body></html>
