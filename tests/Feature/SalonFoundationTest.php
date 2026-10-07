<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceVariant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ServiceCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SalonFoundationTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(string $role = 'administrator'): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', $role)->firstOrFail());
        $this->actingAs($user);

        return $user;
    }

    public static function roles(): array
    {
        return [['administrator', true, true, true, true], ['manager', true, true, true, false], ['receptionist', true, false, false, false], ['beautician', false, false, false, false]];
    }

    #[DataProvider('roles')]
    public function test_roles_enforce_the_permission_matrix(string $role, bool $customers, bool $employees, bool $catalog, bool $users): void
    {
        $user = $this->signIn($role);
        $this->assertSame($customers, $user->can('create', Customer::class));
        $this->assertSame($employees, $user->can('create', Employee::class));
        $this->assertSame($catalog, $user->can('create', Service::class));
        $this->assertSame($catalog, $user->can('create', ServiceCategory::class));
        $this->assertSame($catalog, $user->can('create', ServiceVariant::class));
        $this->assertSame($users, $user->can('create', User::class));
        $this->assertSame($users, $user->can('create', Role::class));
    }

    public function test_reception_cannot_modify_prices_or_users(): void
    {
        $this->signIn('receptionist');
        $service = Service::factory()->create();
        $this->post('/services', ['name' => 'Injected'])->assertForbidden();
        $this->post(route('services.variants.store', $service), ['name' => 'Injected', 'price' => 0])->assertForbidden();
        $this->get('/users')->assertForbidden();
        $this->assertDatabaseMissing('services', ['name' => 'Injected']);
    }

    public function test_beautician_cannot_browse_private_customer_information(): void
    {
        $this->signIn('beautician');
        $customer = Customer::factory()->create(['allergies' => 'Private detail']);
        $this->get('/customers')->assertForbidden();
        $this->get(route('customers.show', $customer))->assertForbidden();
        $this->get('/dashboard')->assertOk()->assertDontSee($customer->full_name)->assertDontSee('Private detail');
        Livewire::test('customer-directory')->assertForbidden();
    }

    public function test_customer_creation_normalizes_contacts_and_ignores_untrusted_fields(): void
    {
        $user = $this->signIn('receptionist');
        $this->post('/customers', ['first_name' => 'Aline', 'last_name' => 'Kabeya', 'phone' => '00 243 (810) 123-456', 'email' => 'ALINE@EXAMPLE.TEST', 'created_by' => 999])->assertSessionHasNoErrors()->assertRedirect();
        $customer = Customer::firstOrFail();
        $this->assertSame('+243810123456', $customer->phone);
        $this->assertSame('aline@example.test', $customer->email);
        $this->assertSame($user->id, $customer->created_by);
        $this->assertMatchesRegularExpression('/^CUS-[0-9]{6,}$/', $customer->number);
    }

    public function test_malformed_customer_contacts_are_rejected(): void
    {
        $this->signIn();
        $this->post('/customers', ['first_name' => 'Aline', 'phone' => ['bad'], 'email' => ['bad']])
            ->assertSessionHasErrors(['phone', 'email']);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_admin_command_creates_a_usable_account(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->artisan('salon:admin', ['email' => 'admin@example.test'])
            ->expectsQuestion('Mot de passe (12 caractères minimum, lettres et chiffres)', 'AdminPassword123')
            ->assertSuccessful();
        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('AdminPassword123', $admin->password));
        $this->assertTrue($admin->hasPermission('users.manage'));
    }

    public function test_duplicate_phone_and_invalid_customer_data_are_rejected(): void
    {
        $this->signIn();
        Customer::factory()->create(['phone' => '+243810123456']);
        $this->post('/customers', ['first_name' => 'Aline', 'phone' => '+243 810 123 456', 'birth_date' => '2999-01-01'])->assertSessionHasErrors(['phone', 'birth_date']);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_customer_can_be_updated_then_archived_without_deletion(): void
    {
        $user = $this->signIn();
        $customer = Customer::factory()->create();
        $this->put(route('customers.update', $customer), ['first_name' => 'Updated', 'phone' => $customer->phone])->assertSessionHasNoErrors();
        $this->assertSame('Updated', $customer->fresh()->first_name);
        $this->assertSame($user->id, $customer->fresh()->updated_by);
        $this->delete(route('customers.destroy', $customer))->assertRedirect(route('customers.index'));
        $this->assertSoftDeleted($customer);
        $this->get(route('customers.show', $customer))->assertNotFound();
    }

    public function test_search_matches_full_names_and_paginates(): void
    {
        $this->signIn();
        Customer::factory()->count(17)->create();
        $customer = Customer::factory()->create(['first_name' => 'Aline', 'last_name' => 'Kabeya']);
        Livewire::test('customer-directory')->set('search', 'Aline Kabeya')->assertSee($customer->phone)->assertSee('1 client(s)');
        Livewire::test('customer-directory')->call('gotoPage', 2)->assertDontSee($customer->phone);
    }

    public function test_employee_creation_saves_multiple_specialties(): void
    {
        $this->signIn('manager');
        $categories = ServiceCategory::factory()->count(2)->create();
        $this->post('/employees', ['name' => 'Sarah', 'position' => 'Coiffeuse', 'active' => 1, 'specialties' => $categories->modelKeys()])->assertSessionHasNoErrors()->assertRedirect();
        $employee = Employee::firstOrFail();
        $this->assertCount(2, $employee->specialties);
        $this->assertDatabaseHas('employees', ['name' => 'Sarah', 'active' => 1]);
    }

    public function test_catalog_is_dynamic_with_decimal_prices_and_images(): void
    {
        $this->signIn();
        Storage::fake('public');
        $this->post('/categories', ['name' => 'Soins du visage', 'active' => 1, 'sort_order' => 6])->assertSessionHasNoErrors();
        $category = ServiceCategory::firstOrFail();
        $this->post('/services', ['name' => 'Soin signature', 'service_category_id' => $category->id, 'default_duration' => 45, 'active' => 1, 'base_price' => '12.50', 'image' => UploadedFile::fake()->image('care.jpg')])->assertSessionHasNoErrors();
        $service = Service::firstOrFail();
        Storage::disk('public')->assertExists($service->image_path);
        $this->post(route('services.variants.store', $service), ['name' => 'Peau sensible', 'price' => '17.25', 'estimated_duration' => 50, 'active' => 1, 'sort_order' => 0])->assertSessionHasNoErrors();
        $variant = ServiceVariant::firstOrFail();
        $this->assertSame('17.25', $variant->price);
        $this->put(route('services.variants.update', [$service, $variant]), ['name' => 'Soin intense', 'price' => '19.95', 'active' => 1, 'sort_order' => 1])->assertSessionHasNoErrors();
        $this->assertSame('19.95', $variant->fresh()->price);
        $this->get(route('services.show', $service))->assertSee('Soin intense')->assertSee('19.95');
    }

    public function test_negative_or_overprecise_prices_are_rejected(): void
    {
        $this->signIn();
        $service = Service::factory()->create();
        foreach (['-1', '2.123', 'invalid'] as $price) {
            $this->post(route('services.variants.store', $service), ['name' => 'Bad', 'price' => $price, 'active' => 1, 'sort_order' => 0])->assertSessionHasErrors('price');
        }
        $this->assertDatabaseCount('service_variants', 0);
    }

    public function test_variant_cannot_be_edited_through_another_service(): void
    {
        $this->signIn();
        $variant = ServiceVariant::factory()->create();
        $other = Service::factory()->create();
        $this->put(route('services.variants.update', [$other, $variant]), ['name' => 'Hijacked', 'price' => '0', 'active' => 1, 'sort_order' => 0])->assertNotFound();
        $this->assertSame('Standard', $variant->fresh()->name);
    }

    public function test_category_with_services_cannot_be_archived(): void
    {
        $this->signIn();
        $service = Service::factory()->create();
        $this->delete(route('categories.destroy', $service->category))->assertSessionHasErrors('category');
        $this->assertNotSoftDeleted($service->category);
    }

    public function test_initial_catalog_is_complete_and_reseeding_preserves_modified_prices(): void
    {
        $this->seed(ServiceCatalogSeeder::class);
        $this->assertDatabaseCount('service_categories', 5);
        $this->assertDatabaseCount('services', 22);
        $this->assertDatabaseCount('service_variants', 36);
        $variant = ServiceVariant::whereHas('service', fn ($q) => $q->where('name', 'Sister Locs Installation'))->firstOrFail();
        $this->assertSame('200.00', $variant->price);
        $variant->update(['price' => '205.00', 'name' => 'Renamed']);
        $this->seed(ServiceCatalogSeeder::class);
        $this->assertSame('205.00', $variant->fresh()->price);
        $this->assertDatabaseCount('service_variants', 36);
    }

    public function test_new_user_has_hashed_password_and_assigned_role(): void
    {
        $this->signIn();
        $role = Role::where('slug', 'receptionist')->firstOrFail();
        $this->post('/users', ['name' => 'Joy', 'email' => 'joy@example.test', 'password' => 'StrongPassword123', 'password_confirmation' => 'StrongPassword123', 'roles' => [$role->id]])->assertSessionHasNoErrors();
        $user = User::where('email', 'joy@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('StrongPassword123', $user->password));
        $this->assertTrue($user->hasPermission('customers.manage'));
        $this->assertFalse($user->hasPermission('catalog.manage'));
    }

    public function test_last_administrator_and_system_role_are_protected(): void
    {
        $user = $this->signIn();
        $role = Role::where('slug', 'beautician')->firstOrFail();
        $this->put(route('users.update', $user), ['name' => $user->name, 'email' => $user->email, 'roles' => [$role->id]])->assertSessionHasErrors('roles');
        $admin = Role::where('slug', 'administrator')->firstOrFail();
        $this->put(route('roles.update', $admin), ['name' => 'Changed', 'slug' => 'changed', 'permissions' => []])->assertSessionHasErrors('role');
        $this->assertTrue($user->fresh()->hasPermission('users.manage'));
    }

    public function test_custom_role_permissions_are_enforced(): void
    {
        $this->signIn();
        $permission = Permission::where('slug', 'catalog.view')->firstOrFail();
        $this->post('/roles', ['name' => 'Consultation', 'slug' => 'consultation', 'permissions' => [$permission->id]])->assertSessionHasNoErrors();
        $role = Role::where('slug', 'consultation')->firstOrFail();
        $user = User::factory()->create();
        $user->roles()->attach($role);
        $this->assertTrue($user->can('viewAny', Service::class));
        $this->assertFalse($user->can('create', Service::class));
    }

    public function test_user_without_permissions_cannot_see_catalog_data_on_dashboard(): void
    {
        $user = User::factory()->create();
        $category = ServiceCategory::factory()->create(['name' => 'Private Category']);
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertDontSee($category->name)->assertDontSee('Découvrir le catalogue');
        $this->get('/services')->assertForbidden();
    }

    public function test_catalog_filters_by_category_and_status(): void
    {
        $this->signIn();
        $visible = Service::factory()->create(['name' => 'Soin unique']);
        $hidden = Service::factory()->create(['name' => 'Autre soin', 'active' => false]);
        Livewire::test('service-catalog')->set('category', (string) $visible->service_category_id)->assertSee('Soin unique')->assertDontSee('Autre soin');
        Livewire::test('service-catalog')->set('status', 'inactive')->assertSee('Autre soin')->assertDontSee('Soin unique');
    }

    public function test_all_phase_one_screens_render_and_escape_customer_notes(): void
    {
        $this->signIn();
        $customer = Customer::factory()->create(['notes' => '<script>alert(1)</script>']);
        $employee = Employee::factory()->create();
        $service = Service::factory()->has(ServiceVariant::factory(), 'variants')->create();
        $role = Role::where('slug', 'manager')->firstOrFail();
        foreach (['/dashboard', '/customers', '/customers/create', '/employees', '/employees/create', '/services', '/services/create', '/categories', '/categories/create', '/users', '/users/create', '/roles', '/roles/create', '/account', route('customers.edit', $customer), route('employees.edit', $employee), route('services.edit', $service), route('categories.edit', $service->category), route('roles.edit', $role), route('users.edit', auth()->user()), route('services.variants.create', $service), route('services.variants.edit', [$service, $service->variants->first()])] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get(route('customers.show', $customer))->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }
}
