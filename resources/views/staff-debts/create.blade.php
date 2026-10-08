<x-layout title="Nouvelle dette de l’équipe">
    <x-page-heading title="Enregistrer une dette" description="Une avance ou un prêt que le membre de l’équipe doit rembourser au salon."><a href="{{ route('staff-debts.index') }}" class="btn-secondary">Dettes de l’équipe</a></x-page-heading>
    @if($employees->isNotEmpty())
        <form method="POST" action="{{ route('staff-debts.store') }}" class="panel max-w-3xl space-y-6 p-6 sm:p-8">
            @csrf<input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $submissionKey) }}">
            <div class="field"><label for="employee_id">Membre de l’équipe *</label><select id="employee_id" name="employee_id" required><option value="">Choisir un membre</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(old('employee_id', $selectedEmployee) == $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
            <x-field name="amount" :label="'Montant accordé ('.config('salon.currency').')'" type="number" min="0.01" max="9999999999.99" step="0.01" required/>
            <div class="grid gap-5 sm:grid-cols-2"><x-field name="issued_on" label="Date de la dette" type="date" :value="today()->format('Y-m-d')" :max="today()->format('Y-m-d')" required/><x-field name="due_on" label="Échéance (facultatif)" type="date"/></div>
            <x-field name="reason" label="Motif" placeholder="Avance sur salaire, prêt personnel…" maxlength="255" required/>
            <x-field name="notes" label="Notes (facultatif)" type="textarea" maxlength="3000"/>
            <div class="flex flex-wrap justify-end gap-3"><a href="{{ route('staff-debts.index') }}" class="btn-secondary">Annuler</a><button class="btn">Enregistrer la dette</button></div>
        </form>
    @else
        <div class="panel max-w-3xl p-8"><x-empty-state title="Aucun membre actif" description="Ajoutez ou réactivez un membre de l’équipe avant d’enregistrer une dette." icon="users"/></div>
    @endif
</x-layout>
