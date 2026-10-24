<x-layout title="Détail de dette">
    <x-page-heading :title="$debt->number" :description="$debt->employee_name_snapshot"><a href="{{ route('staff-debts.index', ['employee_id' => $debt->employee_id]) }}" class="btn-secondary">Dettes de ce membre</a><a href="{{ route('staff-debts.index') }}" class="btn-secondary">Toutes les dettes</a></x-page-heading>
    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="min-w-0 space-y-6">
            <section class="panel p-6 sm:p-8">
                <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="serif text-2xl">{{ $debt->employee_name_snapshot }}</h2><span class="{{ $debt->balance_minor === 0 ? 'badge' : 'badge-muted' }}">{{ $debt->status_label }}</span></div>
                @if($debt->employee->trashed())<p class="mt-3 text-xs text-muted">Ce membre est archivé. Le suivi des remboursements reste disponible.</p>@endif
                <p class="mt-5 break-words font-semibold">{{ $debt->reason }}</p>
                <p class="mt-3 text-sm text-muted">Mode de versement : {{ $debt->payment_method?->label() ?? 'Non renseigné' }}</p>
                @if($debt->payment_method === null)
                    @can('repay', $debt)
                        <form method="POST" action="{{ route('staff-debts.payment-method.update', $debt) }}" class="mt-4 flex flex-wrap items-end gap-3">@csrf @method('PATCH')<div class="field"><label for="loan_payment_method">Compléter le mode de versement initial</label><select id="loan_payment_method" name="payment_method" required><option value="">Choisir le mode utilisé</option>@foreach(\App\Enums\PaymentMethod::cases() as $method)<option value="{{ $method->value }}">{{ $method->label() }}</option>@endforeach</select></div><button class="btn-secondary">Enregistrer</button></form>
                    @endcan
                @endif
                <dl class="mt-6 grid gap-5 sm:grid-cols-3"><div><dt class="eyebrow">Montant initial</dt><dd class="mt-2 break-words font-semibold">{{ \App\Services\Money::decimal($debt->amount_minor) }} {{ $debt->currency }}</dd></div><div><dt class="eyebrow">Remboursé</dt><dd class="mt-2 break-words font-semibold">{{ \App\Services\Money::decimal($debt->repaid_minor) }} {{ $debt->currency }}</dd></div><div><dt class="eyebrow">Solde restant</dt><dd class="mt-2 break-words font-semibold text-wine">{{ \App\Services\Money::decimal($debt->balance_minor) }} {{ $debt->currency }}</dd></div></dl>
                <div class="mt-6 flex flex-wrap gap-x-8 gap-y-3 border-t border-stone-100 pt-5 text-sm text-muted"><p>Date : {{ $debt->issued_on->format('d/m/Y') }}</p><p>Échéance : {{ $debt->due_on?->format('d/m/Y') ?? 'Non définie' }}</p></div>
                @if($debt->notes)<p class="mt-5 whitespace-pre-line break-words text-sm text-muted">{{ $debt->notes }}</p>@endif
                <p class="mt-5 text-xs text-muted">Enregistrée par {{ $debt->actor_name_snapshot }} · {{ $debt->created_at->format('d/m/Y H:i') }}</p>
            </section>
            <section class="panel overflow-hidden"><h2 class="serif p-6 text-2xl">Historique des remboursements</h2><div class="overflow-x-auto"><table class="w-full text-sm">
                <thead class="bg-stone-50"><tr><th scope="col">N°</th><th scope="col">Date</th><th scope="col">Montant</th><th scope="col">Mode / Référence</th><th scope="col">Enregistré par</th></tr></thead>
                <tbody class="divide-y divide-stone-100">@forelse($repayments as $repayment)<tr>
                    <td class="tabular-nums text-muted">{{ $repayments->firstItem() + $loop->index }}</td><td class="whitespace-nowrap">{{ $repayment->paid_on->format('d/m/Y') }}</td><td class="whitespace-nowrap font-semibold">{{ \App\Services\Money::decimal($repayment->amount_minor) }} {{ $debt->currency }}</td>
                    <td class="min-w-44 max-w-sm break-words"><p>{{ $repayment->payment_method->label() }}</p>@if($repayment->reference)<p class="mt-1 text-xs text-muted">{{ $repayment->reference }}</p>@endif @if($repayment->notes)<p class="mt-2 whitespace-pre-line text-xs text-muted">{{ $repayment->notes }}</p>@endif</td>
                    <td><p>{{ $repayment->actor_name_snapshot }}</p><p class="mt-1 whitespace-nowrap text-xs text-muted">{{ $repayment->created_at->format('d/m/Y H:i') }}</p></td>
                </tr>@empty<tr><td colspan="5"><x-empty-state title="Aucun remboursement" description="Les remboursements enregistrés apparaîtront ici." icon="receipt"/></td></tr>@endforelse</tbody>
            </table></div></section>
            {{ $repayments->links() }}
        </div>
        @can('repay', $debt)
            @if($debt->balance_minor > 0)
                <form id="remboursement" method="POST" action="{{ route('staff-debts.repayments.store', $debt) }}" class="panel min-w-0 space-y-5 p-6">
                    @csrf<input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $repaymentKey) }}">
                    <div><h2 class="serif text-2xl">Enregistrer un remboursement</h2><p class="mt-3 text-sm text-muted">Solde : {{ \App\Services\Money::decimal($debt->balance_minor) }} {{ $debt->currency }}</p></div>
                    <x-field name="amount" :label="'Montant reçu ('.$debt->currency.')'" type="number" min="0.01" :max="\App\Services\Money::decimal($debt->balance_minor)" step="0.01" required/>
                    <x-field name="paid_on" label="Date du remboursement" type="date" :value="today()->format('Y-m-d')" :min="$debt->issued_on->format('Y-m-d')" :max="today()->format('Y-m-d')" required/>
                    <div class="field"><label for="payment_method">Mode de remboursement *</label><select id="payment_method" name="payment_method" required>@foreach(\App\Enums\PaymentMethod::cases() as $method)<option value="{{ $method->value }}" @selected(old('payment_method') === $method->value)>{{ $method->label() }}</option>@endforeach</select></div>
                    <x-field name="reference" label="Référence (facultatif)" maxlength="255"/>
                    <x-field name="notes" label="Notes (facultatif)" type="textarea" maxlength="3000"/>
                    <button class="btn min-h-12 w-full">Enregistrer le remboursement</button>
                </form>
            @else
                <div class="panel p-6"><span class="badge"><x-icon name="check"/>Dette soldée</span><p class="mt-3 text-sm text-muted">Le montant accordé a été intégralement remboursé.</p></div>
            @endif
        @endcan
    </div>
</x-layout>
