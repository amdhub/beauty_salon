<x-layout title="Paramètres">
    <x-page-heading title="Paramètres du salon" description="Configurez la réception de vos demandes de rendez-vous."/>
    <form action="{{ route('settings.update') }}" method="POST" class="panel max-w-3xl p-6 sm:p-8">
        @csrf @method('PUT')
        <div class="flex items-center gap-3"><x-icon name="phone" class="size-6 text-wine"/><h2 class="serif text-2xl">Réservations WhatsApp</h2></div>
        <p class="mt-4 text-sm leading-7 text-muted">Après une réservation sur le site, le client est dirigé vers WhatsApp avec les détails de sa demande. Il lui reste à appuyer sur « Envoyer » pour les transmettre au responsable.</p>
        <div class="mt-6"><x-field name="whatsapp_number" label="Numéro WhatsApp du responsable" type="tel" :value="$whatsappNumber" placeholder="+243…" maxlength="30" aria-describedby="whatsapp-number-help"/></div>
        <p id="whatsapp-number-help" class="mt-2 text-xs leading-6 text-muted">Utilisez le numéro associé au compte WhatsApp du responsable, avec son indicatif pays. Laissez ce champ vide pour désactiver la redirection.</p>
        <div class="mt-6 rounded-xl bg-stone-50 p-4 text-sm leading-7 text-muted">La demande est toujours enregistrée dans les réservations du salon, même si le client n’envoie pas le message WhatsApp. La confirmation du rendez-vous reste manuelle.</div>
        <div class="mt-6 flex flex-wrap items-center justify-between gap-4"><span class="{{ $whatsappNumber ? 'badge' : 'badge-muted' }}">{{ $whatsappNumber ? 'Redirection activée' : 'Redirection désactivée' }}</span><button class="btn">Enregistrer les paramètres</button></div>
    </form>
</x-layout>
