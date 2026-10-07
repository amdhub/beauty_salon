<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Role;
use App\Models\SalonVisit;
use App\Models\Service;
use App\Models\ServiceVariant;
use App\Models\User;
use App\Services\VisitCheckout;
use App\Services\VisitPayments;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PointOfSaleTest extends TestCase
{
    use RefreshDatabase;

    private function cashier(string $role = 'receptionist'): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', $role)->firstOrFail());
        $this->actingAs($user);

        return $user;
    }

    private function basket(ServiceVariant $variant): array
    {
        return ['checkout_key' => (string) Str::uuid(), 'customer_mode' => 'walk_in', 'discount' => '0.00', 'payment_method' => 'cash', 'items' => [['service_id' => $variant->service_id, 'service_variant_id' => $variant->id, 'quantity' => 1, 'expected_price' => $variant->price]]];
    }

    public static function roles(): array
    {
        return [['administrator', true], ['manager', true], ['receptionist', true], ['beautician', false]];
    }

    #[DataProvider('roles')]
    public function test_pos_permissions(string $role, bool $allowed): void
    {
        $user = $this->cashier($role);
        $this->assertSame($allowed, $user->can('create', SalonVisit::class));
        if ($allowed) {
            $this->get('/pos')->assertOk()->assertSee('Client de passage');
        } else {
            $this->get('/pos')->assertForbidden();
            Livewire::test('point-of-sale')->assertForbidden();
        }
    }

    public function test_guests_cannot_open_pos_or_see_receipts(): void
    {
        $visit = SalonVisit::factory()->create();
        $this->get('/pos')->assertRedirect('/login');
        $this->get(route('visits.receipt', $visit))->assertRedirect('/login');
        $this->get(route('visits.receipt.thermal', $visit))->assertRedirect('/login');
    }

    public function test_walk_in_checkout_creates_snapshots_and_payment(): void
    {
        $user = $this->cashier();
        $variant = ServiceVariant::factory()->create(['name' => 'Cheveux courts', 'price' => '25.00']);
        $employee = Employee::factory()->create();
        $component = Livewire::test('point-of-sale')->call('add', $variant->service_id, $variant->id)->assertHasNoErrors()
            ->set('employees.'.$variant->service_id.'-'.$variant->id, (string) $employee->id)->call('reviewPayment')->assertSet('amount', '25.00')->call('checkout')->assertHasNoErrors();
        $visit = SalonVisit::with(['items', 'payments'])->firstOrFail();
        $component->assertRedirect(route('visits.receipt.thermal', ['visit' => $visit, 'print' => 1]));
        $this->get(route('visits.receipt.thermal', ['visit' => $visit, 'print' => 1]))
            ->assertOk()->assertSee('data-auto-print="true"', false)->assertSee('Payé');
        $this->assertNull($visit->customer_id);
        $this->assertSame('Client de passage', $visit->customer_name_snapshot);
        $this->assertSame('25.00', $visit->total);
        $this->assertSame(0, $visit->balance_minor);
        $this->assertSame('Payé', $visit->payment_status);
        $this->assertSame('Cheveux courts', $visit->items->first()->variant_name_snapshot);
        $this->assertSame($employee->name, $visit->items->first()->employee_name_snapshot);
        $this->assertSame($user->id, $visit->payments->first()->received_by);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_multiple_services_discount_and_partial_then_mixed_payment(): void
    {
        $this->cashier();
        $one = ServiceVariant::factory()->create(['price' => '20.10']);
        $two = ServiceVariant::factory()->create(['price' => '7.20']);
        Livewire::test('point-of-sale')->call('add', $one->service_id, $one->id)->call('add', $two->service_id, $two->id)
            ->set('discount', '2.30')->call('reviewPayment')->assertSet('amount', '25.00')->set('amount', '10.00')->call('checkout')->assertHasNoErrors();
        $visit = SalonVisit::with('payments')->firstOrFail();
        $this->assertSame('27.30', $visit->subtotal);
        $this->assertSame('2.30', $visit->discount);
        $this->assertSame(1500, $visit->balance_minor);
        $this->post(route('visits.payments.store', $visit), ['amount' => '15.00', 'payment_method' => 'mpesa', 'reference' => 'TX-123', 'idempotency_key' => (string) Str::uuid()])->assertSessionHasNoErrors()->assertRedirect(route('visits.show', $visit));
        $this->assertSame(0, $visit->fresh()->load('payments')->balance_minor);
        $this->assertDatabaseHas('payments', ['payment_method' => 'mpesa', 'reference' => 'TX-123', 'amount' => '15.00']);
    }

    public function test_base_price_quantity_and_exact_cent_arithmetic(): void
    {
        $this->cashier();
        $service = Service::factory()->create(['base_price' => '0.10']);
        Livewire::test('point-of-sale')->call('add', $service->id, null)->call('add', $service->id, null)->call('add', $service->id, null)->call('reviewPayment')->assertSet('amount', '0.30')->call('checkout')->assertHasNoErrors();
        $this->assertDatabaseHas('salon_visits', ['total' => '0.30']);
        $this->assertDatabaseHas('salon_visit_items', ['quantity' => 3, 'service_variant_id' => null]);
    }

    public function test_existing_customer_history_and_quick_creation(): void
    {
        $this->cashier();
        $customer = Customer::factory()->create();
        $variant = ServiceVariant::factory()->create();
        Livewire::test('point-of-sale')->set('customerMode', 'existing')->set('customerSearch', $customer->phone)->assertSee($customer->phone)->set('customerId', (string) $customer->id)->call('add', $variant->service_id, $variant->id)->call('reviewPayment')->call('checkout')->assertHasNoErrors();
        $visit = SalonVisit::firstOrFail();
        $this->assertSame($customer->id, $visit->customer_id);
        $this->get(route('customers.show', $customer))->assertSee($visit->number);
        Livewire::test('point-of-sale')->set('customerMode', 'new')->set('firstName', 'Aline')->set('phone', '00 243 (810) 123-456')->call('add', $variant->service_id, $variant->id)->call('reviewPayment')->call('checkout')->assertHasNoErrors();
        $this->assertDatabaseHas('customers', ['first_name' => 'Aline', 'phone' => '+243810123456']);
        $this->assertDatabaseCount('salon_visits', 2);
    }

    public function test_duplicate_customer_rejects_checkout_without_partial_records(): void
    {
        $this->cashier();
        Customer::factory()->create(['phone' => '+243810123456']);
        $variant = ServiceVariant::factory()->create();
        Livewire::test('point-of-sale')->set('customerMode', 'new')->set('firstName', 'Aline')->set('phone', '+243 810 123 456')->call('add', $variant->service_id, $variant->id)->call('reviewPayment')->call('checkout')->assertHasErrors('phone');
        $this->assertDatabaseCount('salon_visits', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_empty_basket_and_excess_discount_are_rejected(): void
    {
        $this->cashier();
        $variant = ServiceVariant::factory()->create(['price' => '10.00']);
        Livewire::test('point-of-sale')->call('checkout')->assertHasErrors('items');
        Livewire::test('point-of-sale')->call('add', $variant->service_id, $variant->id)->set('discount', '11.00')->call('reviewPayment')->assertHasErrors('discount');
        $this->assertDatabaseCount('salon_visits', 0);
    }

    public function test_payment_cannot_exceed_total_or_remaining_balance(): void
    {
        $user = $this->cashier();
        $variant = ServiceVariant::factory()->create(['price' => '10.00']);
        Livewire::test('point-of-sale')->call('add', $variant->service_id, $variant->id)->call('reviewPayment')->set('amount', '11.00')->call('checkout')->assertHasErrors('amount');
        $this->assertDatabaseCount('salon_visits', 0);
        $visit = app(VisitCheckout::class)->create($user, [...$this->basket($variant), 'amount' => '8.00']);
        $this->post(route('visits.payments.store', $visit), ['amount' => '3.00', 'payment_method' => 'cash', 'idempotency_key' => (string) Str::uuid()])->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('payments', 1);
    }

    public static function invalidAmounts(): array
    {
        return [['-1'], ['0.001'], ['1e2'], ['abc'], ['10000000000']];
    }

    #[DataProvider('invalidAmounts')]
    public function test_invalid_payment_amounts_do_not_create_financial_records(string $amount): void
    {
        $this->cashier();
        $variant = ServiceVariant::factory()->create();
        Livewire::test('point-of-sale')->call('add', $variant->service_id, $variant->id)->set('amount', $amount)->call('checkout')->assertHasErrors('amount');
        $this->assertDatabaseCount('salon_visits', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_changed_price_must_be_reviewed_before_payment(): void
    {
        $this->cashier();
        $variant = ServiceVariant::factory()->create(['price' => '20.00']);
        $component = Livewire::test('point-of-sale')->call('add', $variant->service_id, $variant->id)->call('reviewPayment');
        $variant->update(['price' => '25.00']);
        $component->call('checkout')->assertHasErrors('cart');
        $this->assertDatabaseCount('salon_visits', 0);
        $component->call('refreshPrices')->call('reviewPayment')->assertSet('amount', '25.00')->call('checkout')->assertHasNoErrors();
        $this->assertDatabaseHas('salon_visit_items', ['price_snapshot' => '25.00']);
    }

    public function test_inactive_variants_categories_and_wrong_parent_are_rejected(): void
    {
        $this->cashier();
        $variant = ServiceVariant::factory()->create(['active' => false]);
        $other = Service::factory()->create();
        Livewire::test('point-of-sale')->call('add', $variant->service_id, $variant->id)->assertHasErrors('cart');
        $variant->update(['active' => true]);
        Livewire::test('point-of-sale')->call('add', $other->id, $variant->id)->assertHasErrors('cart');
        $component = Livewire::test('point-of-sale')->call('add', $variant->service_id, $variant->id);
        $variant->service->category->update(['active' => false]);
        $component->call('checkout')->assertHasErrors('cart');
        $this->assertDatabaseCount('salon_visits', 0);
    }

    public function test_inactive_employee_is_rejected_at_checkout(): void
    {
        $this->cashier();
        $employee = Employee::factory()->create(['active' => false]);
        $variant = ServiceVariant::factory()->create();
        Livewire::test('point-of-sale')->call('add', $variant->service_id, $variant->id)->set('employees.'.$variant->service_id.'-'.$variant->id, (string) $employee->id)->call('checkout')->assertHasErrors('cart');
        $this->assertDatabaseCount('salon_visits', 0);
    }

    public function test_retrying_checkout_and_payment_is_idempotent(): void
    {
        $user = $this->cashier();
        $variant = ServiceVariant::factory()->create();
        $data = [...$this->basket($variant), 'amount' => '5.00'];
        $first = app(VisitCheckout::class)->create($user, $data);
        $retry = app(VisitCheckout::class)->create($user, $data);
        $this->assertSame($first->id, $retry->id);
        $this->assertDatabaseCount('salon_visits', 1);
        $this->assertDatabaseCount('payments', 1);
        $payment = ['amount' => '5.00', 'payment_method' => 'cash', 'idempotency_key' => (string) Str::uuid()];
        $this->post(route('visits.payments.store', $first), $payment)->assertSessionHasNoErrors();
        $this->post(route('visits.payments.store', $first), $payment)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('payments', 2);
        $this->assertSame(1000, $first->load('payments')->paid_minor);
    }

    public function test_receipt_preserves_historical_names_prices_and_currency(): void
    {
        $user = $this->cashier();
        $variant = ServiceVariant::factory()->create(['price' => '17.25', 'name' => 'Option originale']);
        $visit = app(VisitCheckout::class)->create($user, $this->basket($variant));
        $variant->update(['price' => '99.00', 'name' => 'Nouveau tarif']);
        $variant->service->update(['name' => 'Nouveau nom']);
        $variant->delete();
        config(['salon.currency' => 'EUR', 'salon.name' => 'Nouveau salon']);
        $this->get(route('visits.receipt', $visit))->assertOk()->assertSee('Option originale')->assertSee('17.25')->assertSee('USD')->assertDontSee('Nouveau tarif')->assertDontSee('Nouveau salon');
        $this->get(route('visits.receipt.thermal', $visit))->assertOk()->assertSee('Option originale')->assertSee('17.25')->assertSee('USD')->assertDontSee('Nouveau tarif')->assertDontSee('Nouveau salon');
    }

    public function test_thermal_receipt_can_be_previewed_and_reprinted_without_changing_payments(): void
    {
        $user = $this->cashier();
        $variant = ServiceVariant::factory()->create(['price' => '25.00', 'name' => '<script>alert(1)</script>']);
        $visit = app(VisitCheckout::class)->create($user, [...$this->basket($variant), 'amount' => '10.00', 'payment_method' => 'mpesa', 'reference' => 'MP-123']);

        $this->get(route('visits.receipt.thermal', $visit))
            ->assertOk()->assertSee('data-auto-print="false"', false)
            ->assertSee('Partiellement payé')->assertSee('15.00 USD')->assertSee('MP-123')
            ->assertSee($variant->name)->assertDontSee($variant->name, false)
            ->assertSee('Imprimer le ticket 80 mm');
        $this->get(route('visits.receipt.thermal', ['visit' => $visit, 'print' => 1]))->assertOk();

        $this->assertDatabaseCount('salon_visits', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->get(route('visits.show', $visit))->assertOk()
            ->assertSee(route('visits.receipt.thermal', ['visit' => $visit, 'print' => 1]));
    }

    public function test_thermal_receipt_shows_unpaid_sales_without_inventing_a_payment(): void
    {
        $user = $this->cashier();
        $variant = ServiceVariant::factory()->create(['price' => '30.00']);
        $visit = app(VisitCheckout::class)->create($user, [...$this->basket($variant), 'amount' => '0.00']);

        $this->get(route('visits.receipt.thermal', $visit))->assertOk()
            ->assertSee('À payer')->assertSee('30.00 USD')->assertSee('Aucun paiement reçu.');
    }

    public function test_beautician_cannot_view_sales_or_record_payments(): void
    {
        $this->cashier('beautician');
        $visit = SalonVisit::factory()->create();
        $this->get('/visits')->assertForbidden();
        $this->get(route('visits.show', $visit))->assertForbidden();
        $this->get(route('visits.receipt', $visit))->assertForbidden();
        $this->get(route('visits.receipt.thermal', $visit))->assertForbidden();
        $this->post(route('visits.payments.store', $visit), ['amount' => '10', 'payment_method' => 'cash', 'idempotency_key' => (string) Str::uuid()])->assertForbidden();
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_zero_payment_keeps_a_balance_and_zero_total_needs_no_payment(): void
    {
        $user = $this->cashier();
        $variant = ServiceVariant::factory()->create(['price' => '10.00']);
        $visit = app(VisitCheckout::class)->create($user, [...$this->basket($variant), 'amount' => '0.00']);
        $this->assertSame(1000, $visit->load('payments')->balance_minor);
        $free = app(VisitCheckout::class)->create($user, [...$this->basket($variant), 'discount' => '10.00']);
        $this->assertSame(0, $free->load('payments')->balance_minor);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_failure_rolls_back_visit_items_and_new_customer(): void
    {
        $user = $this->cashier();
        $variant = ServiceVariant::factory()->create();
        $this->mock(VisitPayments::class, function ($mock): void {
            $mock->shouldReceive('record')->once()->andThrow(new \RuntimeException('Payment storage failure'));
        });
        try {
            app(VisitCheckout::class)->create($user, [...$this->basket($variant), 'customer_mode' => 'new', 'first_name' => 'Rollback', 'phone' => '+243810999999']);
            $this->fail('Checkout should fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Payment storage failure', $exception->getMessage());
        }
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('salon_visits', 0);
        $this->assertDatabaseCount('salon_visit_items', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_browser_cannot_change_locked_cart_prices(): void
    {
        $this->cashier();
        $variant = ServiceVariant::factory()->create();
        $component = Livewire::test('point-of-sale')->call('add', $variant->service_id, $variant->id);
        $this->expectException(CannotUpdateLockedPropertyException::class);
        $component->set('cart.'.$variant->service_id.'-'.$variant->id.'.price_snapshot', '0.01');
    }

    public function test_cart_quantities_can_be_changed_and_removed(): void
    {
        $this->cashier();
        $variant = ServiceVariant::factory()->create(['price' => '15.00']);
        $key = $variant->service_id.'-'.$variant->id;
        Livewire::test('point-of-sale')->call('add', $variant->service_id, $variant->id)->call('changeQuantity', $key, 1)->call('reviewPayment')->assertSet('amount', '30.00')->call('changeQuantity', $key, -1)->call('reviewPayment')->assertSet('amount', '15.00')->call('remove', $key)->assertSet('cart', []);
    }

    public function test_payment_with_a_reused_key_cannot_target_another_sale(): void
    {
        $user = $this->cashier();
        $variant = ServiceVariant::factory()->create();
        $first = app(VisitCheckout::class)->create($user, $this->basket($variant));
        $second = app(VisitCheckout::class)->create($user, [...$this->basket($variant), 'amount' => '0.00']);
        $this->post(route('visits.payments.store', $second), ['amount' => '10.00', 'payment_method' => 'cash', 'idempotency_key' => $first->checkout_key])->assertForbidden();
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_archived_customer_and_invalid_payment_method_are_rejected(): void
    {
        $this->cashier();
        $customer = Customer::factory()->create();
        $customer->delete();
        $variant = ServiceVariant::factory()->create();
        Livewire::test('point-of-sale')->set('customerMode', 'existing')->set('customerId', (string) $customer->id)->call('add', $variant->service_id, $variant->id)->call('checkout')->assertHasErrors('customer_id');
        Livewire::test('point-of-sale')->call('add', $variant->service_id, $variant->id)->set('paymentMethod', 'unrecognized')->call('checkout')->assertHasErrors('payment_method');
        $this->assertDatabaseCount('salon_visits', 0);
    }

    public function test_sales_list_and_filters_render(): void
    {
        $user = $this->cashier();
        $variant = ServiceVariant::factory()->create();
        $visit = app(VisitCheckout::class)->create($user, $this->basket($variant));
        $this->get(route('visits.index', ['search' => $visit->receipt_number]))->assertOk()->assertSee($visit->number);
        $this->get(route('visits.show', $visit))->assertOk()->assertSee('Vente entièrement payée');
        $this->get(route('visits.index', ['search' => 'No match']))->assertDontSee($visit->number);
    }
}
