<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExpensesTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role = 'manager'): User
    {
        $this->seed(RolePermissionSeeder::class);
        $actor = User::factory()->create();
        $actor->roles()->attach(Role::where('slug', $role)->sole());
        $this->actingAs($actor);

        return $actor;
    }

    private function data(array $overrides = []): array
    {
        return [...[
            'label' => 'Achat de vernis', 'category' => 'supplies', 'amount' => '15.25',
            'paid_on' => today()->format('Y-m-d'), 'payment_method' => 'cash',
            'payee' => 'Fournisseur test', 'reference' => 'FACT-123', 'idempotency_key' => (string) Str::uuid(),
        ], ...$overrides];
    }

    public function test_recording_a_paid_expense_preserves_amount_currency_and_author(): void
    {
        $actor = $this->actor();
        config(['salon.currency' => 'USD']);
        $this->get(route('expenses.create'))->assertOk()->assertSee('Montant payé (USD)');
        $this->post(route('expenses.store'), $this->data(['currency' => 'CDF', 'amount_minor' => 1, 'created_by' => 999]))->assertSessionHasNoErrors();

        $expense = Expense::sole();
        $this->assertSame(1525, $expense->amount_minor);
        $this->assertSame('USD', $expense->currency);
        $this->assertSame($actor->id, $expense->created_by);
        $this->assertSame($actor->name, $expense->actor_name_snapshot);
        $this->assertSame('cash', $expense->payment_method->value);
        $this->assertDatabaseCount('payments', 0);
        config(['salon.currency' => 'CDF']);
        $this->get(route('expenses.show', $expense))->assertOk()->assertSee('15.25 USD')->assertSee('Fournisseur test')->assertSee('FACT-123');
    }

    public function test_double_submission_records_the_expense_only_once(): void
    {
        $this->actor();
        $data = $this->data();
        $this->post(route('expenses.store'), $data)->assertSessionHasNoErrors();
        $this->post(route('expenses.store'), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('expenses', 1);
        $this->assertSame(1525, Expense::sole()->amount_minor);
    }

    public static function roles(): array
    {
        return [['administrator', true], ['manager', true], ['receptionist', false], ['beautician', false]];
    }

    #[DataProvider('roles')]
    public function test_expense_permissions(string $role, bool $allowed): void
    {
        $actor = $this->actor($role);
        $expense = Expense::factory()->create();
        $this->assertSame($allowed, $actor->can('viewAny', Expense::class));
        $this->assertSame($allowed, $actor->can('create', Expense::class));
        $this->get(route('expenses.index'))->assertStatus($allowed ? 200 : 403);
        $this->get(route('expenses.show', $expense))->assertStatus($allowed ? 200 : 403);
        $this->post(route('expenses.store'), $this->data())->assertStatus($allowed ? 302 : 403);
        $this->assertDatabaseCount('expenses', $allowed ? 2 : 1);
    }

    public function test_guests_cannot_read_or_record_expenses(): void
    {
        $this->get(route('expenses.index'))->assertRedirect(route('login'));
        $this->post(route('expenses.store'), $this->data())->assertRedirect(route('login'));
        $this->assertDatabaseCount('expenses', 0);
    }

    public static function invalidData(): array
    {
        return [
            'zero' => [['amount' => '0'], 'amount'],
            'negative' => [['amount' => '-1'], 'amount'],
            'precision' => [['amount' => '1.001'], 'amount'],
            'overflow' => [['amount' => '10000000000'], 'amount'],
            'array' => [['amount' => ['1']], 'amount'],
            'missing label' => [['label' => ''], 'label'],
            'invalid category' => [['category' => 'unknown'], 'category'],
            'invalid method' => [['payment_method' => 'unknown'], 'payment_method'],
            'future date' => [['paid_on' => '2099-01-01'], 'paid_on'],
            'invalid date' => [['paid_on' => '2026-02-31'], 'paid_on'],
            'invalid key' => [['idempotency_key' => 'invalid'], 'idempotency_key'],
        ];
    }

    #[DataProvider('invalidData')]
    public function test_invalid_expenses_are_not_persisted(array $overrides, string $field): void
    {
        $this->actor();
        $this->post(route('expenses.store'), $this->data($overrides))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_filters_and_totals_cover_the_entire_selection_and_escape_notes(): void
    {
        $this->actor();
        $actor = User::factory()->create();
        Expense::factory()->count(21)->create(['label' => 'Achat retenu', 'category' => 'supplies', 'amount_minor' => 100, 'paid_on' => '2030-01-10', 'created_by' => $actor->id]);
        Expense::factory()->create(['label' => 'Autre catégorie', 'category' => 'rent', 'amount_minor' => 5000, 'paid_on' => '2030-01-10']);
        Expense::factory()->create(['label' => 'Autre mode', 'category' => 'supplies', 'payment_method' => 'bank', 'paid_on' => '2030-01-10']);
        $filters = ['from' => '2030-01-10', 'to' => '2030-01-10', 'category' => 'supplies', 'payment_method' => 'cash', 'search' => 'Achat', 'page' => 2];
        $response = $this->get(route('expenses.index', $filters))->assertOk()->assertSee('21.00')->assertDontSee('Autre catégorie')->assertDontSee('Autre mode');
        $this->assertSame(21, $response->viewData('expenses')->total());
        $this->assertSame(1, $response->viewData('expenses')->count());
        $this->assertSame(2100, (int) $response->viewData('totals')->sole()->total_minor);
        $note = '<script>alert("test")</script>';
        $expense = Expense::factory()->create(['notes' => $note]);
        $this->get(route('expenses.show', $expense))->assertSee($note)->assertDontSee($note, false);
    }
}
