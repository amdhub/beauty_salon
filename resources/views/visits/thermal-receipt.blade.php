<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $visit->receipt_number }} · {{ $visit->salon_name_snapshot }}</title>
    @vite(['resources/css/app.css', 'resources/css/thermal-receipt.css', 'resources/js/thermal-receipt.js'])
</head>
<body data-auto-print="{{ $autoPrint ? 'true' : 'false' }}">
    <main class="receipt-page">
        <div class="receipt-controls mx-auto mb-6 max-w-2xl space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('visits.show', $visit) }}" class="btn-secondary">Retour à la vente</a>
                @can('create', \App\Models\SalonVisit::class)
                    <a href="{{ route('pos.index') }}" class="btn-secondary">Nouvelle vente</a>
                @endcan
                <button type="button" class="btn" onclick="window.print()">Imprimer le ticket 80 mm</button>
            </div>
            <div class="panel p-4 text-sm">
                <h1 class="font-semibold">Reçu {{ $visit->receipt_number }} · Ticket 80 mm</h1>
                <p class="mt-2 text-muted">Sélectionnez votre imprimante thermique, le papier 80 mm et une échelle de 100 %. Désactivez les en-têtes et pieds de page du navigateur.</p>
                <a href="{{ route('visits.receipt', $visit) }}" class="link mt-2 inline-block">Ouvrir le reçu grand format</a>
            </div>
        </div>
        <article class="thermal-receipt" aria-label="Reçu thermique 80 mm">
            <header class="ticket-center ticket-section">
                <x-brand-logo class="ticket-logo"/>
                <h2>{{ $visit->salon_name_snapshot }}</h2>
                <p>REÇU DE PRESTATIONS</p>
                <p><strong>{{ $visit->receipt_number }}</strong></p>
                <p>{{ $visit->created_at->format('d/m/Y H:i') }} · {{ $visit->number }}</p>
            </header>
            <section class="ticket-section">
                <p>Client : {{ $visit->customer_name_snapshot }}</p>
                @if($visit->customer_phone_snapshot)<p>Tél. : {{ $visit->customer_phone_snapshot }}</p>@endif
                <p>Caisse : {{ $visit->cashier_name_snapshot }}</p>
                <p><strong>{{ $visit->payment_status }}</strong></p>
            </section>
            <section class="ticket-section" aria-label="Prestations">
                @foreach($visit->items as $item)
                    <div class="ticket-item">
                        <p><strong>{{ $item->service_name_snapshot }}</strong></p>
                        @if($item->variant_name_snapshot)<p>{{ $item->variant_name_snapshot }}</p>@endif
                        @if($item->employee_name_snapshot)<p>Avec {{ $item->employee_name_snapshot }}</p>@endif
                        <div class="ticket-row"><span>{{ $item->quantity }} × {{ $item->price_snapshot }}</span><strong>{{ $item->subtotal }} {{ $visit->currency }}</strong></div>
                    </div>
                @endforeach
            </section>
            <dl class="ticket-section ticket-totals">
                <div class="ticket-row"><dt>Sous-total</dt><dd>{{ $visit->subtotal }} {{ $visit->currency }}</dd></div>
                <div class="ticket-row"><dt>Remise</dt><dd>{{ $visit->discount }} {{ $visit->currency }}</dd></div>
                <div class="ticket-row ticket-total"><dt>TOTAL</dt><dd>{{ $visit->total }} {{ $visit->currency }}</dd></div>
                <div class="ticket-row"><dt>Montant payé</dt><dd>{{ \App\Services\Money::decimal($visit->paid_minor) }} {{ $visit->currency }}</dd></div>
                <div class="ticket-row"><dt>Solde restant</dt><dd><strong>{{ \App\Services\Money::decimal($visit->balance_minor) }} {{ $visit->currency }}</strong></dd></div>
            </dl>
            <section class="ticket-section">
                <h3>PAIEMENTS REÇUS</h3>
                @forelse($visit->payments as $payment)
                    <div class="ticket-item">
                        <div class="ticket-row"><span>{{ $payment->payment_method->label() }}</span><strong>{{ $payment->amount }} {{ $visit->currency }}</strong></div>
                        <p>{{ $payment->number }} · {{ $payment->payment_date->format('d/m/Y H:i') }}</p>
                        @if($payment->reference)<p>Réf. {{ $payment->reference }}</p>@endif
                    </div>
                @empty
                    <p>Aucun paiement reçu.</p>
                @endforelse
            </section>
            <footer class="ticket-center">Merci de votre confiance.<br>À bientôt au salon !</footer>
        </article>
    </main>
</body>
</html>
