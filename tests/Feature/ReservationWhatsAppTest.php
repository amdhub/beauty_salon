<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\SalonSetting;
use App\Models\Service;
use App\Models\ServiceVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReservationWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_public_booking_opens_whatsapp_with_public_details_and_server_prices(): void
    {
        $this->travelTo(now()->setDate(2030, 1, 1)->setTime(10, 0));
        config(['salon.name' => 'Nail Master']);
        SalonSetting::factory()->create(['value' => '+243990000123']);
        $variant = ServiceVariant::factory()->create(['name' => 'Pose & couleur', 'price' => '17.25', 'estimated_duration' => 75]);
        $variant->service->update(['name' => 'Manucure']);
        $key = (string) Str::uuid();

        $this->withSession(['booking_key' => $key])->post(route('booking.store'), [
            'request_key' => $key, 'service_option' => $variant->service_id.':'.$variant->id,
            'customer_name' => 'Élodie Test', 'phone' => '+243 990 000 456', 'email' => 'elodie@example.test',
            'preferred_at' => '2030-01-03T14:30', 'customer_notes' => 'Couleur rose & soin + détails ?',
            'price_snapshot' => '0.01', 'internal_notes' => 'Information privée', 'whatsapp_number' => '+33123456789',
        ])->assertSessionHasNoErrors()->assertRedirect(route('booking.received'));

        $reservation = Reservation::sole();
        $this->assertSame('pending', $reservation->status);
        $this->assertDatabaseCount('customers', 0);
        $response = $this->get(route('booking.received'))->assertOk()->assertSee('data-whatsapp-auto-open', false)->assertSee('Ouvrir WhatsApp');
        $url = $response->viewData('whatsappUrl');
        $this->assertSame('https', parse_url($url, PHP_URL_SCHEME));
        $this->assertSame('wa.me', parse_url($url, PHP_URL_HOST));
        $this->assertSame('/243990000123', parse_url($url, PHP_URL_PATH));
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame("Bonjour Nail Master, voici ma demande de réservation.\nRéférence : {$reservation->number}\nStatut : En attente\nClient : Élodie Test\nTéléphone : +243990000456\nE-mail : elodie@example.test\nPrestation : Manucure\nOption : Pose & couleur\nHoraire souhaité : 03/01/2030 à 14:30 (heure du salon)\nDurée : 75 min\nTarif : 17.25 USD\nMessage : Couleur rose & soin + détails ?\nDemande en attente de confirmation par le salon.", $query['text']);

        $this->get(route('booking.received'))->assertOk()->assertSee('Ouvrir WhatsApp')->assertDontSee('data-whatsapp-auto-open', false);
    }

    public function test_without_a_configured_number_booking_succeeds_without_a_whatsapp_redirect(): void
    {
        $service = Service::factory()->create(['base_price' => '25.00']);
        $key = (string) Str::uuid();
        $this->get(route('booking.create'))->assertDontSee('WhatsApp s’ouvrira');

        $this->withSession(['booking_key' => $key])->post(route('booking.store'), [
            'request_key' => $key, 'service_option' => $service->id.':base', 'customer_name' => 'Nadia Test',
            'phone' => '+243990000456', 'preferred_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('booking.received'));

        $this->assertDatabaseCount('reservations', 1);
        $this->get(route('booking.received'))->assertOk()->assertViewHas('whatsappUrl', null)->assertDontSee('Ouvrir WhatsApp')->assertDontSee('data-whatsapp-auto-open', false);
    }

    public function test_changed_or_removed_setting_takes_effect_on_the_next_request(): void
    {
        $setting = SalonSetting::factory()->create(['value' => '+243990000123']);
        $reservation = Reservation::factory()->create();
        $this->withSession(['last_reservation_id' => $reservation->id])->get(route('booking.received'))->assertSee('https://wa.me/243990000123');

        $setting->update(['value' => '+243990000789']);
        $this->get(route('booking.received'))->assertSee('https://wa.me/243990000789')->assertDontSee('https://wa.me/243990000123');

        $setting->update(['value' => null]);
        $this->get(route('booking.received'))->assertViewHas('whatsappUrl', null)->assertDontSee('Ouvrir WhatsApp');
    }

    public function test_whatsapp_shares_only_the_session_reservation_and_excludes_staff_notes(): void
    {
        SalonSetting::factory()->create(['value' => '+243990000123']);
        $reservation = Reservation::factory()->create(['customer_name' => 'Ma réservation', 'internal_notes' => 'Secret du salon', 'processed_by_name' => 'Employé privé', 'status' => 'confirmed', 'scheduled_at' => '2030-01-04 15:00:00', 'customer_notes' => '<script>alert("x")</script>']);
        $other = Reservation::factory()->create(['customer_name' => 'Autre client privé']);

        $response = $this->withSession(['last_reservation_id' => $reservation->id])->get(route('booking.received', ['id' => $other->id]))->assertOk()->assertDontSee('<script>alert("x")</script>', false);

        parse_str(parse_url($response->viewData('whatsappUrl'), PHP_URL_QUERY), $query);
        $this->assertStringContainsString('Client : Ma réservation', $query['text']);
        $this->assertStringContainsString('Horaire retenu : 04/01/2030 à 15:00', $query['text']);
        $this->assertStringNotContainsString('en attente de confirmation', $query['text']);
        $this->assertStringNotContainsString('Secret du salon', $query['text']);
        $this->assertStringNotContainsString('Employé privé', $query['text']);
        $this->assertStringNotContainsString('Autre client privé', $query['text']);
        $this->assertStringNotContainsString('E-mail :', $query['text']);
        $this->assertStringNotContainsString('Option :', $query['text']);
    }
}
