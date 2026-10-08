<x-layout title="Dettes de l’équipe">
    <x-page-heading title="Dettes & remboursements de l’équipe" description="Suivez les avances et prêts accordés aux membres du salon.">
        <a href="{{ route('employees.index') }}" class="btn-secondary">Équipe</a>
        @can('create', \App\Models\StaffDebt::class)<a href="{{ route('staff-debts.create', ['employee_id' => $filters['employee_id'] ?? null]) }}" class="btn"><x-icon name="plus"/>Enregistrer une dette</a>@endcan
    </x-page-heading>
    <form method="GET" class="panel mb-6 grid items-end gap-4 p-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="field"><label for="employee_id">Membre de l’équipe</label><select id="employee_id" name="employee_id"><option value="">Toute l’équipe</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(($filters['employee_id'] ?? '') == $employee->id)>{{ $employee->name }}{{ $employee->trashed() ? ' (archivé)' : '' }}</option>@endforeach</select></div>
        <div class="field"><label for="status">État de la dette</label><select id="status" name="status"><option value="">Toutes les dettes</option>@foreach(['open' => 'À rembourser', 'paid' => 'Soldées', 'overdue' => 'En retard'] as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
        <x-field name="search" label="Nom ou motif" :value="$filters['search'] ?? ''" maxlength="150"/>
        <div class="flex flex-wrap gap-2"><button class="btn">Filtrer</button><a href="{{ route('staff-debts.index') }}" class="btn-secondary" aria-label="Effacer les filtres"><x-icon name="close"/></a></div>
    </form>
    @foreach($totals as $total)
        <div class="mb-6"><p class="mb-3 text-xs text-muted">Totaux de la sélection · {{ $total->currency }}</p><div class="grid gap-4 sm:grid-cols-3">
            <div class="panel p-5"><p class="eyebrow">Montant accordé</p><p class="mt-2 break-words text-2xl font-semibold">{{ \App\Services\Money::decimal((int) $total->total_minor) }} <span class="text-sm">{{ $total->currency }}</span></p></div>
            <div class="panel p-5"><p class="eyebrow">Remboursé</p><p class="mt-2 break-words text-2xl font-semibold">{{ \App\Services\Money::decimal((int) $total->total_repaid_minor) }} <span class="text-sm">{{ $total->currency }}</span></p></div>
            <div class="panel p-5"><p class="eyebrow">Solde restant</p><p class="mt-2 break-words text-2xl font-semibold text-wine">{{ \App\Services\Money::decimal((int) $total->total_balance_minor) }} <span class="text-sm">{{ $total->currency }}</span></p></div>
        </div></div>
    @endforeach
    <div class="panel overflow-hidden"><div class="overflow-x-auto"><table class="w-full text-sm">
        <thead class="bg-stone-50"><tr><th scope="col">N°</th><th scope="col">Membre / Référence</th><th scope="col">Motif</th><th scope="col">Montant</th><th scope="col">Remboursé</th><th scope="col">Solde</th><th scope="col">Échéance / État</th><th scope="col">Actions</th></tr></thead>
        <tbody class="divide-y divide-stone-100">
            @forelse($debts as $debt)<tr>
                <td class="tabular-nums text-muted">{{ $debts->firstItem() + $loop->index }}</td>
                <td><p class="font-semibold">{{ $debt->employee_name_snapshot }}</p><p class="mt-1 whitespace-nowrap text-xs text-muted">{{ $debt->number }} · {{ $debt->issued_on->format('d/m/Y') }}</p></td>
                <td class="min-w-40 max-w-xs break-words">{{ $debt->reason }}</td>
                <td class="whitespace-nowrap">{{ \App\Services\Money::decimal($debt->amount_minor) }} {{ $debt->currency }}</td>
                <td class="whitespace-nowrap">{{ \App\Services\Money::decimal($debt->repaid_minor) }} {{ $debt->currency }}</td>
                <td class="whitespace-nowrap font-semibold text-wine">{{ \App\Services\Money::decimal($debt->balance_minor) }} {{ $debt->currency }}</td>
                <td><p class="whitespace-nowrap">{{ $debt->due_on?->format('d/m/Y') ?? 'Sans échéance' }}</p><span class="mt-2 {{ $debt->balance_minor === 0 ? 'badge' : 'badge-muted' }}">{{ $debt->status_label }}</span></td>
                <td><div class="flex items-center gap-2"><x-icon-action :href="route('staff-debts.show', $debt)" :label="'Consulter la dette '.$debt->number"/>@can('repay', $debt)@if($debt->balance_minor > 0)<x-icon-action :href="route('staff-debts.show', $debt).'#remboursement'" :label="'Rembourser la dette '.$debt->number" icon="stock-in"/>@endif@endcan</div></td>
            </tr>@empty<tr><td colspan="8"><x-empty-state title="Aucune dette trouvée" description="Enregistrez une avance ou un prêt, ou adaptez les filtres." icon="receipt"/></td></tr>@endforelse
        </tbody>
    </table></div></div>
    <div class="mt-6">{{ $debts->links() }}</div>
</x-layout>
