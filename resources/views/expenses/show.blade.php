<x-layout title="Détail de dépense">
    <x-page-heading :title="$expense->number" :description="$expense->label"><a href="{{ route('expenses.index') }}" class="btn-secondary">Toutes les dépenses</a></x-page-heading>
    <section class="panel max-w-3xl p-6 sm:p-8">
        <span class="badge-muted">{{ $expense->category_label }}</span><h2 class="serif mt-5 break-words text-2xl">{{ $expense->label }}</h2><p class="mt-4 text-3xl font-semibold text-wine">{{ \App\Services\Money::decimal($expense->amount_minor) }} {{ $expense->currency }}</p>
        <dl class="mt-7 grid gap-6 text-sm sm:grid-cols-2"><div><dt class="eyebrow">Date du règlement</dt><dd class="mt-2">{{ $expense->paid_on->format('d/m/Y') }}</dd></div><div><dt class="eyebrow">Mode de paiement</dt><dd class="mt-2">{{ $expense->payment_method->label() }}</dd></div><div><dt class="eyebrow">Bénéficiaire</dt><dd class="mt-2 break-words">{{ $expense->payee ?: 'Non renseigné' }}</dd></div><div><dt class="eyebrow">Référence du paiement</dt><dd class="mt-2 break-words">{{ $expense->reference ?: 'Non renseignée' }}</dd></div></dl>
        @if($expense->notes)<p class="mt-6 whitespace-pre-line break-words border-t border-stone-100 pt-5 text-sm text-muted">{{ $expense->notes }}</p>@endif
        <p class="mt-6 text-xs text-muted">Enregistrée par {{ $expense->actor_name_snapshot }} · {{ $expense->created_at->format('d/m/Y H:i') }}</p>
        @can('view', \App\Services\FinancialReport::class)<a href="{{ route('financial-reports.index', ['from' => $expense->paid_on->format('Y-m-d'), 'to' => $expense->paid_on->format('Y-m-d'), 'currency' => $expense->currency, 'payment_method' => $expense->payment_method->value]) }}" class="btn-secondary mt-6">Voir le rapport de cette journée</a>@endcan
    </section>
</x-layout>
