<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\SalonSetting;

class ReservationWhatsApp
{
    public function recipient(): ?string
    {
        $number = SalonSetting::where('key', SalonSetting::RESERVATION_WHATSAPP_NUMBER)->value('value');

        return is_string($number) && preg_match('/^\+[1-9][0-9]{7,14}$/D', $number) ? $number : null;
    }

    public function url(Reservation $reservation): ?string
    {
        $recipient = $this->recipient();
        if (! $recipient) {
            return null;
        }

        $lines = [
            'Bonjour '.config('salon.name').', voici ma demande de réservation.',
            'Référence : '.$reservation->number,
            'Statut : '.$reservation->status_label,
            'Client : '.$reservation->customer_name,
            'Téléphone : '.$reservation->phone,
        ];
        if ($reservation->email) {
            $lines[] = 'E-mail : '.$reservation->email;
        }
        $lines[] = 'Prestation : '.$reservation->service_name_snapshot;
        if ($reservation->variant_name_snapshot) {
            $lines[] = 'Option : '.$reservation->variant_name_snapshot;
        }
        $lines[] = 'Horaire souhaité : '.$reservation->preferred_at->format('d/m/Y à H:i').' (heure du salon)';
        if ($reservation->scheduled_at) {
            $lines[] = 'Horaire retenu : '.$reservation->scheduled_at->format('d/m/Y à H:i').' (heure du salon)';
        }
        $lines[] = 'Durée : '.$reservation->duration_minutes.' min';
        $lines[] = 'Tarif : '.$reservation->price_snapshot.' '.$reservation->currency;
        if ($reservation->customer_notes) {
            $lines[] = 'Message : '.$reservation->customer_notes;
        }
        if ($reservation->status === 'pending') {
            $lines[] = 'Demande en attente de confirmation par le salon.';
        }

        return 'https://wa.me/'.substr($recipient, 1).'?text='.rawurlencode(implode("\n", $lines));
    }
}
