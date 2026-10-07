<x-layout title="Ventes & reçus">
<x-page-heading title="Ventes & reçus" description="Retrouvez les ventes, les règlements et les soldes à encaisser.">
@can('create',\App\Models\SalonVisit::class)<a href="{{ route('pos.index') }}" class="btn"><x-icon name="plus"/>Nouvelle vente</a>@endcan
</x-page-heading>
<form method="GET" action="{{ route('visits.index') }}" class="panel mb-6 flex flex-wrap items-end gap-3 p-4">
<div class="field min-w-40 flex-1"><label for="sale-search">Client, téléphone ou numéro de reçu</label><input id="sale-search" name="search" value="{{ $filters['search']??'' }}" placeholder="Client de passage, REC-000001…"/></div>
<div class="field"><label for="sale-date">Date de vente</label><input id="sale-date" type="date" name="date" value="{{ $filters['date']??'' }}"/></div>
<button class="btn-secondary min-h-11">Filtrer</button><a href="{{ route('visits.index') }}" class="link p-3 text-xs">Réinitialiser</a>
</form>
<div class="panel overflow-hidden">@if($visits->isNotEmpty())<div class="overflow-x-auto"><table class="w-full"><thead class="bg-stone-50"><tr><th scope="col">N°</th><th>Vente / Date</th><th>Client</th><th>Total</th><th>Encaissé</th><th>Solde</th><th>Statut</th><th>Actions</th></tr></thead><tbody class="divide-y divide-stone-100">
@foreach($visits as $visit)<tr><td class="tabular-nums text-muted">{{ $visits->firstItem() + $loop->index }}</td><td class="whitespace-nowrap"><a class="link" href="{{ route('visits.show',$visit) }}">{{ $visit->number }}</a><p class="mt-1 text-xs text-muted">{{ $visit->created_at->format('d/m/Y H:i') }}</p></td><td>{{ $visit->customer_name_snapshot }}<p class="mt-1 text-xs text-muted">{{ $visit->items_count }} prestation(s)</p></td><td class="whitespace-nowrap">{{ $visit->total }} {{ $visit->currency }}</td><td class="whitespace-nowrap">{{ \App\Services\Money::decimal($visit->paid_minor) }} {{ $visit->currency }}</td><td class="whitespace-nowrap font-semibold">{{ \App\Services\Money::decimal($visit->balance_minor) }} {{ $visit->currency }}</td><td><span class="{{ $visit->balance_minor===0 ? 'badge' : 'badge-muted' }} whitespace-nowrap">{{ $visit->payment_status }}</span></td><td><x-icon-action :href="route('visits.show', $visit)" :label="'Voir la vente '.$visit->number"/></td></tr>@endforeach
</tbody></table></div><div class="p-5">{{ $visits->links() }}</div>@else<x-empty-state title="Aucune vente pour cette sélection" description="Commencez depuis la caisse ou ajustez vos filtres." icon="receipt"/>@endif</div>
</x-layout>
