<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReservationAlertsTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role = 'administrator'): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', $role)->firstOrFail());
        $this->actingAs($user);

        return $user;
    }

    public function test_a_public_booking_appears_in_admin_notifications_and_dashboard(): void
    {
        $service = Service::factory()->create(['base_price' => '25.00']);
        $this->get(route('booking.create'))->assertOk();
        $this->post(route('booking.store'), [
            'request_key' => session('booking_key'), 'service_option' => $service->id.':base',
            'customer_name' => 'Cliente réservation publique', 'phone' => '+243990000001',
            'preferred_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ])->assertSessionHasNoErrors();
        $reservation = Reservation::sole();
        $this->actor();

        $this->get(route('dashboard'))->assertOk()->assertSee('1 réservation attend votre attention')->assertSee('Cliente réservation publique')->assertSee(route('reservations.show', $reservation));
        $this->get(route('account'))->assertOk()->assertSee('Cliente réservation publique')->assertSee('1 réservation(s) à traiter');
        Livewire::test('reservation-alerts')->assertSee('Cliente réservation publique')->assertSet('pendingCount', 1);
    }

    public function test_refresh_detects_new_requests_and_removes_processed_requests(): void
    {
        $this->actor('receptionist');
        $bell = Livewire::test('reservation-alerts')->assertSet('pendingCount', 0)->assertSee('Aucune notification pour le moment');
        $banner = Livewire::test('reservation-alerts', ['mode' => 'banner'])->assertDontSee('Voir les demandes');
        $reservation = Reservation::factory()->create(['customer_name' => 'Nouvelle demande']);

        $bell->call('$refresh')->assertSet('pendingCount', 1)->assertSee('Nouvelle demande');
        $banner->call('$refresh')->assertSee('1 réservation attend votre attention');
        $this->put(route('reservations.update', $reservation), ['status' => 'rejected', 'expected_status' => 'pending'])->assertSessionHasNoErrors();
        $bell->call('$refresh')->assertSet('pendingCount', 0)->assertDontSee('Nouvelle demande')->assertSee('Aucune notification pour le moment');
        $banner->call('$refresh')->assertDontSee('Voir les demandes');
    }

    public function test_only_pending_requests_are_counted_and_the_five_latest_are_listed(): void
    {
        $this->actor('manager');
        Reservation::factory()->create(['customer_name' => 'Demande ancienne']);
        Reservation::factory()->count(5)->create();
        $latest = Reservation::factory()->create(['customer_name' => 'Dernière demande']);
        Reservation::factory()->create(['status' => 'confirmed', 'customer_name' => 'Déjà confirmée']);
        Reservation::factory()->create(['status' => 'cancelled', 'customer_name' => 'Déjà annulée']);
        Reservation::factory()->create(['status' => 'completed', 'customer_name' => 'Déjà terminée']);

        Livewire::test('reservation-alerts')->assertSet('pendingCount', 7)->assertSee('Dernière demande')
            ->assertSee(route('reservations.show', $latest))->assertDontSee('Demande ancienne')
            ->assertDontSee('Déjà confirmée')->assertDontSee('Déjà annulée')->assertDontSee('Déjà terminée')
            ->assertSee(route('reservations.index', ['status' => 'pending']));
    }

    public function test_users_without_permission_cannot_see_reservation_alerts_or_client_details(): void
    {
        $this->actor('beautician');
        Reservation::factory()->create(['customer_name' => 'Identité confidentielle']);

        $this->get(route('dashboard'))->assertOk()->assertDontSee('Identité confidentielle')->assertDontSee('Voir les demandes');
        Livewire::test('reservation-alerts')->assertSet('pendingCount', 0)->assertDontSee('Identité confidentielle');
        Livewire::test('reservation-alerts', ['mode' => 'banner'])->assertDontSee('Voir les demandes');
    }

    public function test_permissions_are_checked_again_when_notifications_refresh(): void
    {
        $user = $this->actor();
        Reservation::factory()->create(['customer_name' => 'Réservation privée']);
        $bell = Livewire::test('reservation-alerts')->assertSee('Réservation privée');
        $user->roles()->detach();
        $this->actingAs($user->fresh());

        $bell->call('$refresh')->assertSet('pendingCount', 0)->assertDontSee('Réservation privée');
    }

    public function test_guests_cannot_load_the_notification_component(): void
    {
        Livewire::test('reservation-alerts')->assertForbidden();
    }

    public function test_customer_names_are_escaped_and_private_notes_are_not_exposed(): void
    {
        $this->actor();
        Reservation::factory()->create(['customer_name' => '<script>alert(1)</script>', 'internal_notes' => 'Notes internes confidentielles']);

        Livewire::test('reservation-alerts')->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('Notes internes confidentielles');
    }
}
