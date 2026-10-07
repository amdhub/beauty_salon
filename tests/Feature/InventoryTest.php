<?php

namespace Tests\Feature;

use App\Models\ConsumableProduct;
use App\Models\Employee;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role = 'manager'): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', $role)->firstOrFail());
        $this->actingAs($user);

        return $user;
    }

    private function movement(array $overrides = []): array
    {
        return [...['quantity' => '100.000', 'movement_date' => today()->format('Y-m-d'), 'idempotency_key' => (string) Str::uuid(), 'notes' => 'Soins du salon'], ...$overrides];
    }

    private function productData(array $overrides = []): array
    {
        return [...['name' => 'Shampooing hydratant', 'reference' => 'SH-001', 'unit' => 'ml', 'minimum_stock' => '50.000', 'active' => true], ...$overrides];
    }

    public static function roles(): array
    {
        return [['administrator', true, true, true], ['manager', true, true, true], ['receptionist', true, false, true], ['beautician', false, false, false]];
    }

    #[DataProvider('roles')]
    public function test_inventory_permissions(string $role, bool $view, bool $manage, bool $consume): void
    {
        $actor = $this->actor($role);
        $product = ConsumableProduct::factory()->create();

        $this->assertSame($view, $actor->can('viewAny', ConsumableProduct::class));
        $this->assertSame($manage, $actor->can('create', ConsumableProduct::class));
        $this->assertSame($manage, $actor->can('update', $product));
        $this->assertSame($manage, $actor->can('supply', $product));
        $this->assertSame($consume, $actor->can('consume', $product));
        $this->get(route('products.index'))->assertStatus($view ? 200 : 403);
    }

    public function test_guests_cannot_view_or_change_stock(): void
    {
        $product = ConsumableProduct::factory()->create();

        $this->get(route('products.index'))->assertRedirect('/login');
        $this->get(route('products.show', $product))->assertRedirect('/login');
        $this->post(route('products.movements.store', [$product, 'supply']), $this->movement())->assertRedirect('/login');
        $this->assertSame('0.000', $product->fresh()->stock);
    }

    public function test_unauthorized_users_cannot_bypass_hidden_actions(): void
    {
        $this->actor('receptionist');
        $product = ConsumableProduct::factory()->create();
        $this->get(route('products.create'))->assertForbidden();
        $this->get(route('products.edit', $product))->assertForbidden();
        $this->post(route('products.store'), $this->productData())->assertForbidden();
        $this->put(route('products.update', $product), $this->productData())->assertForbidden();
        $this->post(route('products.movements.store', [$product, 'supply']), $this->movement())->assertForbidden();

        $this->actor('beautician');
        $this->get(route('products.show', $product))->assertForbidden();
        $this->get(route('products.movements.index'))->assertForbidden();
        $this->get(route('products.movements.create', [$product, 'consume']))->assertForbidden();
        $this->post(route('products.movements.store', [$product, 'consume']), $this->movement())->assertForbidden();
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_product_creation_and_update_cannot_set_stock_directly(): void
    {
        $this->actor();
        $this->get(route('products.create'))->assertOk();
        $this->post(route('products.store'), $this->productData(['stock' => '999', 'reference' => ' sh-001 ']))->assertSessionHasNoErrors();
        $product = ConsumableProduct::sole();
        $this->assertSame('0.000', $product->stock);
        $this->assertSame('SH-001', $product->reference);
        $this->get(route('products.edit', $product))->assertOk();
        $this->put(route('products.update', $product), $this->productData(['stock' => '222', 'name' => 'Nouveau nom']))->assertRedirect(route('products.show', $product));
        $this->assertSame('0.000', $product->fresh()->stock);
        $this->assertSame('Nouveau nom', $product->fresh()->name);
        $this->post(route('products.store'), $this->productData())->assertSessionHasErrors('reference');
    }

    public function test_supply_and_fractional_consumption_update_stock_and_preserve_history(): void
    {
        $actor = $this->actor();
        $product = ConsumableProduct::factory()->create(['name' => 'Crème visage', 'unit' => 'g']);
        $employee = Employee::factory()->create();
        $this->get(route('products.movements.create', [$product, 'supply']))->assertOk()->assertSee('Fournisseur');
        $this->post(route('products.movements.store', [$product, 'supply']), $this->movement(['quantity' => '1.125', 'supplier' => 'Beauté Distribution', 'total_cost' => '12.35', 'reference' => 'BL-001']))->assertSessionHasNoErrors()->assertRedirect(route('products.show', $product));
        $this->assertSame('1.125', $product->fresh()->stock);
        $this->get(route('products.movements.create', [$product, 'consume']))->assertOk()->assertSee($employee->name);
        $this->post(route('products.movements.store', [$product, 'consume']), $this->movement(['quantity' => '0.025', 'employee_id' => $employee->id, 'total_cost' => '999']))->assertSessionHasNoErrors();

        $this->assertSame('1.100', $product->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', ['type' => 'supply', 'supplier' => 'Beauté Distribution', 'total_cost' => '12.35', 'currency' => 'USD', 'created_by' => $actor->id]);
        $this->assertDatabaseHas('stock_movements', ['type' => 'consume', 'quantity' => '0.025', 'stock_after' => '1.100', 'total_cost' => null, 'employee_name_snapshot' => $employee->name]);
        $product->update(['name' => 'Autre produit']);
        $employee->delete();
        $actor->update(['name' => 'Nouveau responsable']);
        $this->get(route('products.show', $product))->assertOk()->assertSee('Crème visage')->assertSee('Beauté Distribution')->assertSee($employee->name)->assertSee('12.35 USD');
    }

    public function test_stock_cannot_become_negative_even_with_a_stale_form(): void
    {
        $this->actor();
        $product = ConsumableProduct::factory()->create();
        $this->post(route('products.movements.store', [$product, 'supply']), $this->movement())->assertSessionHasNoErrors();
        $this->post(route('products.movements.store', [$product, 'consume']), $this->movement(['quantity' => '70']))->assertSessionHasNoErrors();
        $this->post(route('products.movements.store', [$product, 'consume']), $this->movement(['quantity' => '70']))->assertSessionHasErrors('quantity');
        $this->assertSame('30.000', $product->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->post(route('products.movements.store', [$product, 'consume']), $this->movement(['quantity' => '30']))->assertSessionHasNoErrors();
        $this->assertSame('0.000', $product->fresh()->stock);
    }

    public function test_double_submission_records_only_one_movement_and_cannot_target_another_product(): void
    {
        $this->actor();
        $product = ConsumableProduct::factory()->create();
        $data = $this->movement();
        $this->post(route('products.movements.store', [$product, 'supply']), $data)->assertSessionHasNoErrors();
        $this->post(route('products.movements.store', [$product, 'supply']), $data)->assertSessionHasNoErrors();
        $this->assertSame('100.000', $product->fresh()->stock);
        $other = ConsumableProduct::factory()->create();
        $this->post(route('products.movements.store', [$other, 'supply']), $data)->assertSessionHasErrors('idempotency_key');
        $this->post(route('products.movements.store', [$product, 'consume']), $data)->assertSessionHasErrors('idempotency_key');
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertSame('0.000', $other->fresh()->stock);
    }

    public static function invalidQuantities(): array
    {
        return [['0'], ['-1'], ['1.0001'], ['1e2'], ['1000000000'], ['abc'], [['1']]];
    }

    #[DataProvider('invalidQuantities')]
    public function test_invalid_quantities_do_not_change_stock(mixed $quantity): void
    {
        $this->actor();
        $product = ConsumableProduct::factory()->create();
        $this->post(route('products.movements.store', [$product, 'supply']), $this->movement(['quantity' => $quantity]))->assertSessionHasErrors('quantity');
        $this->assertSame('0.000', $product->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_piece_quantities_must_be_whole_and_stock_cannot_overflow(): void
    {
        $this->actor();
        $product = ConsumableProduct::factory()->create(['unit' => 'piece', 'stock' => '999999999.000']);
        $this->post(route('products.movements.store', [$product, 'consume']), $this->movement(['quantity' => '0.5']))->assertSessionHasErrors('quantity');
        $this->post(route('products.movements.store', [$product, 'supply']), $this->movement(['quantity' => '1']))->assertSessionHasErrors('quantity');
        $this->assertSame('999999999.000', $product->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_inactive_products_and_archived_employees_cannot_receive_movements(): void
    {
        $this->actor();
        $product = ConsumableProduct::factory()->create(['active' => false, 'stock' => '100.000']);
        $this->post(route('products.movements.store', [$product, 'supply']), $this->movement())->assertSessionHasErrors('quantity');
        $this->post(route('products.movements.store', [$product, 'consume']), $this->movement())->assertSessionHasErrors('quantity');
        $product->update(['active' => true]);
        $employee = Employee::factory()->create();
        $employee->delete();
        $this->post(route('products.movements.store', [$product, 'consume']), $this->movement(['employee_id' => $employee->id]))->assertSessionHasErrors('employee_id');
        $this->assertSame('100.000', $product->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_unit_is_locked_after_first_movement_and_stock_remains_visible_when_inactive(): void
    {
        $this->actor();
        $product = ConsumableProduct::factory()->create();
        $this->post(route('products.movements.store', [$product, 'supply']), $this->movement())->assertSessionHasNoErrors();
        $this->put(route('products.update', $product), $this->productData(['unit' => 'l']))->assertSessionHasErrors('unit');
        $this->assertSame('ml', $product->fresh()->unit);
        $this->put(route('products.update', $product), $this->productData(['active' => false]))->assertSessionHasNoErrors();
        $this->get(route('products.index', ['status' => 'inactive']))->assertOk()->assertSee('Shampooing hydratant')->assertSee('100.000');
    }

    public function test_movements_validate_dates_costs_and_consumption_reason(): void
    {
        $this->actor();
        $this->freezeTime();
        $product = ConsumableProduct::factory()->create();
        $this->post(route('products.movements.store', [$product, 'supply']), $this->movement(['movement_date' => today()->addDay()->format('Y-m-d'), 'total_cost' => '-1']))->assertSessionHasErrors(['movement_date', 'total_cost']);
        $this->post(route('products.movements.store', [$product, 'consume']), $this->movement(['notes' => '']))->assertSessionHasErrors('notes');
        $this->post(route('products.movements.store', [$product, 'wrong']), $this->movement())->assertNotFound();
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_low_stock_search_and_history_filters(): void
    {
        $this->actor();
        $this->freezeTime();
        $product = ConsumableProduct::factory()->create(['name' => 'Shampooing test', 'reference' => 'SH-99', 'minimum_stock' => '100.000']);
        ConsumableProduct::factory()->create(['name' => 'Stock suffisant', 'stock' => '101.000', 'minimum_stock' => '100.000']);
        $this->post(route('products.movements.store', [$product, 'supply']), $this->movement())->assertSessionHasNoErrors();
        $this->get(route('products.index', ['status' => 'low']))->assertOk()->assertSee('Shampooing test')->assertDontSee('Stock suffisant');
        $this->get(route('products.index', ['search' => 'SH-99']))->assertOk()->assertSee('Shampooing test')->assertDontSee('Stock suffisant');
        $this->get(route('products.movements.index', ['type' => 'supply', 'from' => today()->format('Y-m-d'), 'to' => today()->format('Y-m-d')]))->assertOk()->assertSee('Shampooing test');
        $this->get(route('products.movements.index', ['type' => 'consume']))->assertOk()->assertSee('Aucun mouvement');
        $this->get(route('products.movements.index', ['to' => today()->format('Y-m-d')]))->assertOk()->assertSee('Shampooing test');
    }

    public function test_a_failed_ledger_insert_rolls_back_the_stock_change(): void
    {
        $this->actor();
        $product = ConsumableProduct::factory()->create();
        StockMovement::creating(function (): void {
            throw new \RuntimeException('Ledger unavailable');
        });
        try {
            $this->post(route('products.movements.store', [$product, 'supply']), $this->movement())->assertStatus(500);
            $this->assertSame('0.000', $product->fresh()->stock);
            $this->assertDatabaseCount('stock_movements', 0);
        } finally {
            StockMovement::flushEventListeners();
        }
    }
}
