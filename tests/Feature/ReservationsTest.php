<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceVariant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReservationsTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role = 'receptionist'): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', $role)->firstOrFail());
        $this->actingAs($user);

        return $user;
    }

    private function booking(Service $service, array $overrides = []): array
    {
        $key = (string) Str::uuid();
        $this->withSession(['booking_key' => $key]);

        return [...[
            'request_key' => $key, 'service_option' => $service->id.':base', 'customer_name' => 'Nadia Test',
            'phone' => '+243 990 000 111', 'email' => 'nadia@example.test', 'preferred_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
        ], ...$overrides];
    }

    public function test_public_landing_and_booking_show_only_active_bookable_services(): void
    {
        $service = Service::factory()->create(['name' => 'Soin disponible', 'base_price' => '25.00', 'notes' => 'Note privée du salon']);
        Service::factory()->create(['name' => 'Soin inactif', 'active' => false]);
        Service::factory()->create(['name' => 'Catégorie masquée', 'service_category_id' => ServiceCategory::factory()->create(['active' => false])->id]);
        Service::factory()->create(['name' => 'Sans tarif', 'base_price' => null]);
        $archived = Service::factory()->create(['name' => 'Soin archivé']);
        $archived->delete();

        $this->get('/')->assertOk()->assertSee('Soin disponible')->assertSee('25.00')->assertDontSee('Soin inactif')->assertDontSee('Catégorie masquée')->assertDontSee('Sans tarif')->assertDontSee('Soin archivé')->assertDontSee('Note privée du salon');
        $this->get(route('booking.create', ['service' => $service->id]))->assertOk()->assertSee('Soin disponible')->assertSessionHas('booking_key')->assertDontSee('Soin inactif');
        $this->get(route('home', ['category' => $service->service_category_id]))->assertOk()->assertSee('Soin disponible');
    }

    public function test_booking_is_pending_with_server_prices_and_does_not_create_sales_or_change_clients(): void
    {
        $service = Service::factory()->create(['base_price' => '25.00', 'default_duration' => 30]);
        $data = $this->booking($service, ['price_snapshot' => '0.01', 'status' => 'confirmed', 'internal_notes' => 'Injected']);

        $this->post(route('booking.store'), $data)->assertSessionHasNoErrors()->assertRedirect(route('booking.received'));

        $reservation = Reservation::sole();
        $this->assertSame('pending', $reservation->status);
        $this->assertSame('25.00', $reservation->price_snapshot);
        $this->assertSame(30, $reservation->duration_minutes);
        $this->assertSame('+243990000111', $reservation->phone);
        $this->assertNull($reservation->internal_notes);
        $this->assertNull($reservation->scheduled_at);
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('salon_visits', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->get(route('booking.received'))->assertOk()->assertSee($reservation->number)->assertSee('pas encore confirmé');
    }

    public function test_variant_booking_preserves_historical_price_and_duration(): void
    {
        $variant = ServiceVariant::factory()->create(['price' => '17.25', 'estimated_duration' => 75, 'name' => 'Cheveux longs']);
        $service = $variant->service;
        $this->post(route('booking.store'), $this->booking($service, ['service_option' => $service->id.':'.$variant->id]))->assertSessionHasNoErrors();
        $variant->update(['price' => '99.00', 'name' => 'Nouveau tarif']);
        $variant->delete();
        $service->delete();
        $reservation = Reservation::sole();
        $this->assertSame('17.25', $reservation->price_snapshot);
        $this->assertSame(75, $reservation->duration_minutes);
        $this->get(route('booking.received'))->assertOk()->assertSee('Cheveux longs')->assertSee('17.25')->assertDontSee('Nouveau tarif');
    }

    public function test_unavailable_or_mismatched_options_are_rejected(): void
    {
        $service = Service::factory()->create(['base_price' => null]);
        $this->post(route('booking.store'), $this->booking($service))->assertSessionHasErrors('service_option');
        $variant = ServiceVariant::factory()->create();
        $this->post(route('booking.store'), $this->booking($service, ['service_option' => $service->id.':'.$variant->id]))->assertSessionHasErrors('service_option');
        $service->update(['active' => false]);
        $this->post(route('booking.store'), $this->booking($service))->assertSessionHasErrors('service_option');
        $service->update(['active' => true, 'base_price' => '10.00']);
        $service->category->delete();
        $this->post(route('booking.store'), $this->booking($service))->assertSessionHasErrors('service_option');
        $this->assertDatabaseCount('reservations', 0);
    }

    public static function invalidBookingData(): array
    {
        return [
            'past date' => [['preferred_at' => '2000-01-01T10:00'], 'preferred_at'],
            'invalid date' => [['preferred_at' => '2026-02-31T10:00'], 'preferred_at'],
            'phone array' => [['phone' => ['unexpected']], 'phone'],
            'name missing' => [['customer_name' => ''], 'customer_name'],
            'service array' => [['service_option' => ['unexpected']], 'service_option'],
            'invalid email' => [['email' => 'not-an-email'], 'email'],
        ];
    }

    #[DataProvider('invalidBookingData')]
    public function test_invalid_public_details_are_rejected(array $overrides, string $field): void
    {
        $service = Service::factory()->create(['base_price' => '10.00']);
        $this->post(route('booking.store'), $this->booking($service, $overrides))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_dates_beyond_booking_horizon_are_rejected(): void
    {
        $this->freezeTime();
        $service = Service::factory()->create(['base_price' => '10.00']);
        $this->post(route('booking.store'), $this->booking($service, ['preferred_at' => now()->addDays(91)->format('Y-m-d\TH:i')]))->assertSessionHasErrors('preferred_at');
    }

    public function test_a_duplicate_submission_is_idempotent_and_a_new_form_allows_a_new_booking(): void
    {
        $service = Service::factory()->create(['base_price' => '10.00']);
        $data = $this->booking($service);
        $this->post(route('booking.store'), $data)->assertSessionHasNoErrors();
        $this->post(route('booking.store'), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('reservations', 1);
        $this->get(route('booking.create'))->assertOk();
        $this->assertNotSame($data['request_key'], session('booking_key'));
        $this->post(route('booking.store'), [...$data, 'request_key' => session('booking_key')])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('reservations', 2);
    }

    public function test_a_foreign_booking_key_cannot_expose_another_customers_reservation(): void
    {
        $other = Reservation::factory()->create(['customer_name' => 'Client privé']);
        $service = Service::factory()->create(['base_price' => '10.00']);
        $this->post(route('booking.store'), $this->booking($service, ['request_key' => $other->request_key]))->assertSessionHasErrors('request_key');
        $this->get(route('booking.received', ['id' => $other->id]))->assertRedirect(route('booking.create'));
        $this->get(route('reservations.show', $other))->assertRedirect('/login');
        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_public_confirmation_does_not_disclose_internal_notes(): void
    {
        $reservation = Reservation::factory()->create(['internal_notes' => 'Note confidentielle']);
        $this->withSession(['last_reservation_id' => $reservation->id])->get(route('booking.received'))->assertOk()->assertDontSee('Note confidentielle');
    }

    public function test_booking_submission_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('booking.store'), []);
        }
        $this->post(route('booking.store'), [])->assertTooManyRequests();
    }

    public static function roles(): array
    {
        return [['administrator', true], ['manager', true], ['receptionist', true], ['beautician', false]];
    }

    #[DataProvider('roles')]
    public function test_reservation_permissions(string $role, bool $allowed): void
    {
        $actor = $this->actor($role);
        $reservation = Reservation::factory()->create();
        $this->assertSame($allowed, $actor->can('viewAny', Reservation::class));
        $this->assertSame($allowed, $actor->can('update', $reservation));
        $this->get(route('reservations.index'))->assertStatus($allowed ? 200 : 403);
        $this->get(route('reservations.show', $reservation))->assertStatus($allowed ? 200 : 403);
        if (! $allowed) {
            $this->put(route('reservations.update', $reservation), ['status' => 'rejected', 'expected_status' => 'pending'])->assertForbidden();
        }
    }

    public function test_staff_can_confirm_reschedule_and_complete_a_reservation(): void
    {
        $actor = $this->actor();
        $this->freezeTime();
        $reservation = Reservation::factory()->create();
        $scheduled = now()->addDays(2)->format('Y-m-d\TH:i');
        $this->put(route('reservations.update', $reservation), ['status' => 'confirmed', 'expected_status' => 'pending', 'scheduled_at' => $scheduled, 'internal_notes' => 'Client contacté'])->assertSessionHasNoErrors()->assertRedirect(route('reservations.show', $reservation));
        $this->assertSame('confirmed', $reservation->fresh()->status);
        $this->assertSame($scheduled, $reservation->fresh()->scheduled_at->format('Y-m-d\TH:i'));
        $this->assertSame($actor->id, $reservation->fresh()->processed_by);
        $this->put(route('reservations.update', $reservation), ['status' => 'completed', 'expected_status' => 'confirmed'])->assertSessionHasErrors('status');
        $this->travel(3)->days();
        $this->put(route('reservations.update', $reservation), ['status' => 'completed', 'expected_status' => 'confirmed'])->assertSessionHasNoErrors();
        $this->assertSame('completed', $reservation->fresh()->status);
        $this->get(route('reservations.show', $reservation))->assertOk()->assertSee('Demande clôturée');
    }

    public function test_stale_decisions_and_invalid_transitions_are_rejected(): void
    {
        $this->actor();
        $reservation = Reservation::factory()->create();
        $this->put(route('reservations.update', $reservation), ['status' => 'completed', 'expected_status' => 'pending'])->assertSessionHasErrors('status');
        $this->put(route('reservations.update', $reservation), ['status' => 'confirmed', 'expected_status' => 'pending'])->assertSessionHasErrors('scheduled_at');
        $this->put(route('reservations.update', $reservation), ['status' => 'rejected', 'expected_status' => 'pending'])->assertSessionHasNoErrors();
        $this->put(route('reservations.update', $reservation), ['status' => 'confirmed', 'expected_status' => 'pending', 'scheduled_at' => now()->addDays(2)->format('Y-m-d\TH:i')])->assertSessionHasErrors('status');
        $this->assertSame('rejected', $reservation->fresh()->status);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_confirming_a_public_booking_creates_a_client_visible_in_the_customer_list(): void
    {
        $service = Service::factory()->create(['base_price' => '25.00']);
        $this->post(route('booking.store'), $this->booking($service, ['email' => 'Nadia@Example.test']))->assertSessionHasNoErrors();
        $reservation = Reservation::sole();
        $actor = $this->actor();

        $this->put(route('reservations.update', $reservation), ['status' => 'confirmed', 'expected_status' => 'pending', 'scheduled_at' => now()->addDays(2)->format('Y-m-d\TH:i')])
            ->assertSessionHasNoErrors()->assertRedirect(route('reservations.show', $reservation));

        $customer = Customer::sole();
        $this->assertSame('Nadia Test', $customer->full_name);
        $this->assertSame('+243990000111', $customer->phone);
        $this->assertSame('nadia@example.test', $customer->email);
        $this->assertSame($actor->id, $customer->created_by);
        $this->assertSame($customer->id, $reservation->fresh()->customer_id);
        $this->get(route('customers.index'))->assertOk()->assertSee('Nadia Test');
        $this->get(route('reservations.show', $reservation))->assertOk()->assertSee(route('customers.show', $customer));
    }

    public function test_confirmations_reuse_the_phone_match_without_overwriting_client_details(): void
    {
        $customer = Customer::factory()->create(['first_name' => 'Nadia', 'last_name' => 'Client', 'phone' => '+243990000001', 'email' => 'original@example.test', 'notes' => 'À conserver']);
        $this->actor();
        $reservation = Reservation::factory()->create(['customer_name' => 'Nom du formulaire', 'email' => 'nouveau@example.test']);
        $data = ['status' => 'confirmed', 'expected_status' => 'pending', 'scheduled_at' => now()->addDays(2)->format('Y-m-d\TH:i')];

        $this->put(route('reservations.update', $reservation), $data)->assertSessionHasNoErrors();
        $other = Reservation::factory()->create();
        $this->put(route('reservations.update', $other), $data)->assertSessionHasNoErrors();
        $customer->refresh();
        $this->assertDatabaseCount('customers', 1);
        $this->assertSame($customer->id, $reservation->fresh()->customer_id);
        $this->assertSame($customer->id, $other->fresh()->customer_id);
        $this->assertSame('Nadia Client', $customer->full_name);
        $this->assertSame('original@example.test', $customer->email);
        $this->assertSame('À conserver', $customer->notes);
    }

    public function test_a_long_booking_name_is_preserved_and_the_created_client_remains_editable(): void
    {
        $this->actor();
        $name = str_repeat('É', 150);
        $reservation = Reservation::factory()->create(['customer_name' => $name]);

        $this->put(route('reservations.update', $reservation), ['status' => 'confirmed', 'expected_status' => 'pending', 'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i')])->assertSessionHasNoErrors();
        $customer = Customer::sole();
        $this->assertSame($name, $customer->full_name);
        $this->put(route('customers.update', $customer), ['first_name' => $name, 'phone' => $customer->phone, 'notes' => 'Fiche complétée'])->assertSessionHasNoErrors();
        $this->assertSame('Fiche complétée', $customer->fresh()->notes);
    }

    public function test_rescheduling_keeps_the_link_even_after_the_client_phone_changes(): void
    {
        $this->actor();
        $reservation = Reservation::factory()->create();
        $this->put(route('reservations.update', $reservation), ['status' => 'confirmed', 'expected_status' => 'pending', 'scheduled_at' => now()->addDays(2)->format('Y-m-d\TH:i')])->assertSessionHasNoErrors();
        $customer = Customer::sole();
        $customer->update(['phone' => '+243990000222']);

        $this->put(route('reservations.update', $reservation), ['status' => 'confirmed', 'expected_status' => 'confirmed', 'scheduled_at' => now()->addDays(3)->format('Y-m-d\TH:i')])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('customers', 1);
        $this->assertSame($customer->id, $reservation->fresh()->customer_id);
        $this->assertSame('+243990000222', $customer->fresh()->phone);
    }

    public function test_confirming_a_returning_archived_client_restores_the_existing_record(): void
    {
        $customer = Customer::factory()->create(['phone' => '+243990000001']);
        $customer->delete();
        $this->actor();
        $reservation = Reservation::factory()->create();

        $this->put(route('reservations.update', $reservation), ['status' => 'confirmed', 'expected_status' => 'pending', 'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i')])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('customers', 1);
        $this->assertNotSoftDeleted($customer);
        $this->assertSame($customer->id, $reservation->fresh()->customer_id);
    }

    public function test_shared_email_does_not_merge_clients_with_different_phones(): void
    {
        $existing = Customer::factory()->create(['phone' => '+243990000222', 'email' => 'famille@example.test']);
        $this->actor();
        $reservation = Reservation::factory()->create(['email' => 'Famille@Example.test']);

        $this->put(route('reservations.update', $reservation), ['status' => 'confirmed', 'expected_status' => 'pending', 'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i')])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('customers', 2);
        $reservation->refresh();
        $this->assertNotSame($existing->id, $reservation->customer_id);
        $this->assertSame('+243990000001', $reservation->customer->phone);
        $this->assertNull($reservation->customer->email);
        $this->assertSame('Famille@Example.test', $reservation->email);
        $this->assertSame('famille@example.test', $existing->fresh()->email);
    }

    public function test_unauthorized_confirmation_cannot_register_a_client(): void
    {
        $this->actor('beautician');
        $reservation = Reservation::factory()->create();

        $this->put(route('reservations.update', $reservation), ['status' => 'confirmed', 'expected_status' => 'pending', 'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i')])->assertForbidden();

        $this->assertDatabaseCount('customers', 0);
        $this->assertSame('pending', $reservation->fresh()->status);
        $this->assertNull($reservation->fresh()->customer_id);
    }

    public function test_staff_filters_and_escaped_customer_notes(): void
    {
        $this->actor();
        $reservation = Reservation::factory()->create(['customer_name' => 'Nadia', 'customer_notes' => '<script>alert(1)</script>']);
        Reservation::factory()->create(['customer_name' => 'Autre client', 'status' => 'cancelled']);
        $this->get(route('reservations.index', ['status' => 'pending', 'search' => 'Nadia', 'date' => $reservation->preferred_at->format('Y-m-d')]))->assertOk()->assertSee('Nadia')->assertDontSee('Autre client');
        $this->get(route('reservations.show', $reservation))->assertOk()->assertSee($reservation->customer_notes)->assertDontSee($reservation->customer_notes, false);
    }
}
