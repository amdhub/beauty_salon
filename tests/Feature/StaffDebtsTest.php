<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\StaffDebt;
use App\Models\StaffRepayment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StaffDebtsTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role = 'manager'): User
    {
        $this->seed(RolePermissionSeeder::class);
        $actor = User::factory()->create();
        $actor->roles()->attach(Role::where('slug', $role)->firstOrFail());
        $this->actingAs($actor);

        return $actor;
    }

    private function debtData(Employee $employee, array $overrides = []): array
    {
        return [...[
            'employee_id' => $employee->id, 'amount' => '100.50', 'issued_on' => today()->format('Y-m-d'),
            'due_on' => today()->addMonth()->format('Y-m-d'), 'reason' => 'Avance personnelle', 'idempotency_key' => (string) Str::uuid(),
        ], ...$overrides];
    }

    private function repaymentData(array $overrides = []): array
    {
        return [...[
            'amount' => '25.25', 'paid_on' => today()->format('Y-m-d'), 'payment_method' => 'cash', 'idempotency_key' => (string) Str::uuid(),
        ], ...$overrides];
    }

    public function test_debt_creation_and_partial_then_full_repayment_update_the_ledger_and_balance(): void
    {
        $actor = $this->actor();
        config(['salon.currency' => 'USD']);
        $employee = Employee::factory()->create(['name' => 'Nadia Équipe']);
        $this->get(route('staff-debts.create', ['employee_id' => $employee->id]))->assertOk()->assertSee('Nadia Équipe');

        $this->post(route('staff-debts.store'), $this->debtData($employee, ['currency' => 'CDF', 'repaid_minor' => 10050, 'created_by' => 999, 'employee_name_snapshot' => 'Faux nom']))->assertSessionHasNoErrors();

        $debt = StaffDebt::sole();
        $this->assertSame(10050, $debt->amount_minor);
        $this->assertSame(10050, $debt->balance_minor);
        $this->assertSame(0, $debt->repaid_minor);
        $this->assertSame('USD', $debt->currency);
        $this->assertSame('Nadia Équipe', $debt->employee_name_snapshot);
        $this->assertSame($actor->id, $debt->created_by);
        $this->assertSame('À rembourser', $debt->status_label);
        $this->get(route('staff-debts.show', $debt))->assertOk()->assertSee('100.50 USD')->assertSee('Aucun remboursement');

        $this->post(route('staff-debts.repayments.store', $debt), $this->repaymentData(['reference' => 'REF-001']))->assertSessionHasNoErrors()->assertRedirect(route('staff-debts.show', $debt));
        $debt->refresh();
        $this->assertSame(7525, $debt->balance_minor);
        $this->assertSame('Partiellement remboursée', $debt->status_label);
        $this->assertDatabaseHas('staff_repayments', ['staff_debt_id' => $debt->id, 'amount_minor' => 2525, 'created_by' => $actor->id, 'reference' => 'REF-001']);

        $this->post(route('staff-debts.repayments.store', $debt), $this->repaymentData(['amount' => '75.25', 'payment_method' => 'mpesa']))->assertSessionHasNoErrors();
        $this->assertSame(0, $debt->fresh()->balance_minor);
        $this->assertSame('Soldée', $debt->fresh()->status_label);
        $this->assertSame(10050, (int) StaffRepayment::sum('amount_minor'));
        $this->assertDatabaseCount('staff_repayments', 2);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('salon_visits', 0);
        $this->get(route('staff-debts.show', $debt))->assertOk()->assertSee('Dette soldée')->assertSee('M-Pesa')->assertDontSee('id="remboursement"', false);
        $this->get(route('employees.index'))->assertOk()->assertSee('Dettes et remboursements de Nadia Équipe');
    }

    public static function roles(): array
    {
        return [['administrator', true], ['manager', true], ['receptionist', false], ['beautician', false]];
    }

    #[DataProvider('roles')]
    public function test_staff_finances_are_restricted_to_authorized_roles(string $role, bool $allowed): void
    {
        $actor = $this->actor($role);
        $debt = StaffDebt::factory()->create();
        $this->assertSame($allowed, $actor->can('viewAny', StaffDebt::class));
        $this->assertSame($allowed, $actor->can('create', StaffDebt::class));
        $this->assertSame($allowed, $actor->can('repay', $debt));
        $this->get(route('staff-debts.index'))->assertStatus($allowed ? 200 : 403);
        $this->get(route('staff-debts.show', $debt))->assertStatus($allowed ? 200 : 403);
        $this->get(route('staff-debts.create'))->assertStatus($allowed ? 200 : 403);
        if (! $allowed) {
            $this->post(route('staff-debts.store'), $this->debtData($debt->employee))->assertForbidden();
            $this->post(route('staff-debts.repayments.store', $debt), $this->repaymentData())->assertForbidden();
            $this->assertDatabaseCount('staff_debts', 1);
            $this->assertDatabaseCount('staff_repayments', 0);
            $this->get(route('dashboard'))->assertDontSee(route('staff-debts.index'));
        }
    }

    public function test_guests_cannot_read_or_write_staff_finances(): void
    {
        $debt = StaffDebt::factory()->create();
        $this->get(route('staff-debts.index'))->assertRedirect(route('login'));
        $this->get(route('staff-debts.show', $debt))->assertRedirect(route('login'));
        $this->post(route('staff-debts.store'), $this->debtData($debt->employee))->assertRedirect(route('login'));
        $this->post(route('staff-debts.repayments.store', $debt), $this->repaymentData())->assertRedirect(route('login'));
        $this->assertDatabaseCount('staff_debts', 1);
        $this->assertDatabaseCount('staff_repayments', 0);
    }

    public function test_view_permission_alone_does_not_allow_financial_entries(): void
    {
        $actor = $this->actor();
        $role = Role::create(['name' => 'Consultation', 'slug' => 'finance-reader']);
        $role->permissions()->attach(Permission::where('slug', 'staff_debts.view')->sole());
        $actor->roles()->sync([$role->id]);
        $actor->unsetRelation('roles');
        $debt = StaffDebt::factory()->create();

        $this->get(route('staff-debts.show', $debt))->assertOk()->assertDontSee('id="remboursement"', false);
        $this->post(route('staff-debts.store'), $this->debtData($debt->employee))->assertForbidden();
        $this->post(route('staff-debts.repayments.store', $debt), $this->repaymentData())->assertForbidden();
        $this->assertDatabaseCount('staff_repayments', 0);
    }

    public function test_double_submissions_do_not_duplicate_debts_or_repayments(): void
    {
        $this->actor();
        $employee = Employee::factory()->create();
        $data = $this->debtData($employee);
        $this->post(route('staff-debts.store'), $data)->assertSessionHasNoErrors();
        $this->post(route('staff-debts.store'), $data)->assertSessionHasNoErrors();
        $debt = StaffDebt::sole();
        $repayment = $this->repaymentData(['amount' => '100.50']);
        $this->post(route('staff-debts.repayments.store', $debt), $repayment)->assertSessionHasNoErrors();
        $this->post(route('staff-debts.repayments.store', $debt), $repayment)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('staff_debts', 1);
        $this->assertDatabaseCount('staff_repayments', 1);
        $this->assertSame(0, $debt->fresh()->balance_minor);
    }

    public function test_reused_submission_keys_cannot_affect_another_employee_or_debt(): void
    {
        $this->actor();
        $employee = Employee::factory()->create();
        $other = Employee::factory()->create();
        $data = $this->debtData($employee);
        $this->post(route('staff-debts.store'), $data)->assertSessionHasNoErrors();
        $this->post(route('staff-debts.store'), [...$data, 'employee_id' => $other->id])->assertSessionHasErrors('idempotency_key');
        $debt = StaffDebt::sole();
        $otherDebt = StaffDebt::factory()->create(['employee_id' => $other->id]);
        $data = $this->repaymentData();
        $this->post(route('staff-debts.repayments.store', $debt), $data)->assertSessionHasNoErrors();
        $this->post(route('staff-debts.repayments.store', $otherDebt), $data)->assertSessionHasErrors('idempotency_key');
        $this->assertSame(0, $otherDebt->fresh()->repaid_minor);
        $this->assertDatabaseCount('staff_repayments', 1);
    }

    public function test_overpayment_and_stale_balance_are_rejected_without_changing_the_ledger(): void
    {
        $this->actor();
        $debt = StaffDebt::factory()->create(['amount_minor' => 10000]);
        $this->post(route('staff-debts.repayments.store', $debt), $this->repaymentData(['amount' => '70.00']))->assertSessionHasNoErrors();
        $this->post(route('staff-debts.repayments.store', $debt), $this->repaymentData(['amount' => '40.00']))->assertSessionHasErrors(['amount' => 'Le remboursement doit être supérieur à zéro et ne pas dépasser le solde restant.']);
        $this->assertSame(3000, $debt->fresh()->balance_minor);
        $this->assertDatabaseCount('staff_repayments', 1);
        $this->post(route('staff-debts.repayments.store', $debt), $this->repaymentData(['amount' => '30.00']))->assertSessionHasNoErrors();
        $this->post(route('staff-debts.repayments.store', $debt), $this->repaymentData(['amount' => '0.01']))->assertSessionHasErrors('amount');
        $this->assertSame(0, $debt->fresh()->balance_minor);
        $this->assertDatabaseCount('staff_repayments', 2);
    }

    public static function invalidDebts(): array
    {
        return [
            'zero' => [['amount' => '0'], 'amount'],
            'negative' => [['amount' => '-5'], 'amount'],
            'precision' => [['amount' => '10.001'], 'amount'],
            'oversize' => [['amount' => '10000000000'], 'amount'],
            'array' => [['amount' => ['1']], 'amount'],
            'missing reason' => [['reason' => ''], 'reason'],
            'nonexistent staff' => [['employee_id' => 999999], 'employee_id'],
            'future date' => [['issued_on' => '2099-01-01'], 'issued_on'],
            'early due date' => [['due_on' => '2000-01-01'], 'due_on'],
            'invalid key' => [['idempotency_key' => 'wrong'], 'idempotency_key'],
        ];
    }

    #[DataProvider('invalidDebts')]
    public function test_invalid_debts_are_not_recorded(array $overrides, string $field): void
    {
        $this->actor();
        $employee = Employee::factory()->create();
        $this->post(route('staff-debts.store'), $this->debtData($employee, $overrides))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('staff_debts', 0);
    }

    public static function invalidRepayments(): array
    {
        return [
            'zero' => [['amount' => '0'], 'amount'],
            'negative' => [['amount' => '-1'], 'amount'],
            'precision' => [['amount' => '0.001'], 'amount'],
            'array' => [['amount' => ['1']], 'amount'],
            'scientific' => [['amount' => '1e2'], 'amount'],
            'future date' => [['paid_on' => '2099-01-01'], 'paid_on'],
            'before debt' => [['paid_on' => '2000-01-01'], 'paid_on'],
            'invalid method' => [['payment_method' => 'unknown'], 'payment_method'],
        ];
    }

    #[DataProvider('invalidRepayments')]
    public function test_invalid_repayments_leave_the_balance_unchanged(array $overrides, string $field): void
    {
        $this->actor();
        $debt = StaffDebt::factory()->create();
        $this->post(route('staff-debts.repayments.store', $debt), $this->repaymentData($overrides))->assertSessionHasErrors($field);
        $this->assertSame(10000, $debt->fresh()->balance_minor);
        $this->assertDatabaseCount('staff_repayments', 0);
    }

    public function test_archived_staff_keep_their_history_and_can_repay_but_cannot_receive_new_debts(): void
    {
        $this->actor();
        $employee = Employee::factory()->create(['name' => 'Nom historique']);
        $this->post(route('staff-debts.store'), $this->debtData($employee))->assertSessionHasNoErrors();
        $debt = StaffDebt::sole();
        $employee->update(['name' => 'Nouveau nom']);
        $employee->delete();
        config(['salon.currency' => 'CDF']);

        $this->get(route('staff-debts.show', $debt))->assertOk()->assertSee('Nom historique')->assertSee('100.50 USD')->assertSee('Ce membre est archivé');
        $this->post(route('staff-debts.repayments.store', $debt), $this->repaymentData())->assertSessionHasNoErrors();
        $this->assertSame(7525, $debt->fresh()->balance_minor);
        $this->post(route('staff-debts.store'), $this->debtData($employee))->assertSessionHasErrors('employee_id');
        $inactive = Employee::factory()->create(['active' => false]);
        $this->post(route('staff-debts.store'), $this->debtData($inactive))->assertSessionHasErrors('employee_id');
        $this->assertDatabaseCount('staff_debts', 1);
    }

    public function test_employee_status_filters_and_totals_do_not_mix_currencies(): void
    {
        $this->actor();
        $this->freezeTime();
        $employee = Employee::factory()->create();
        $open = StaffDebt::factory()->create(['employee_id' => $employee->id, 'amount_minor' => 10000, 'due_on' => today()->subDay(), 'reason' => 'Dette en retard']);
        $paid = StaffDebt::factory()->create(['employee_id' => $employee->id, 'amount_minor' => 5000, 'reason' => 'Dette soldée']);
        $this->post(route('staff-debts.repayments.store', $paid), $this->repaymentData(['amount' => '50.00']))->assertSessionHasNoErrors();
        StaffDebt::factory()->create(['employee_id' => $employee->id, 'currency' => 'CDF', 'amount_minor' => 300000]);
        StaffDebt::factory()->create(['reason' => 'Autre employé']);

        $response = $this->get(route('staff-debts.index', ['employee_id' => $employee->id]))->assertOk()->assertDontSee('Autre employé');
        $totals = $response->viewData('totals')->keyBy('currency');
        $this->assertSame(15000, (int) $totals['USD']->total_minor);
        $this->assertSame(5000, (int) $totals['USD']->total_repaid_minor);
        $this->assertSame(10000, (int) $totals['USD']->total_balance_minor);
        $this->assertSame(300000, (int) $totals['CDF']->total_balance_minor);
        $this->assertSame('En retard', $open->status_label);
        $this->get(route('staff-debts.index', ['employee_id' => $employee->id, 'status' => 'overdue']))->assertSee('Dette en retard')->assertDontSee('Dette soldée');
        $this->get(route('staff-debts.index', ['employee_id' => $employee->id, 'status' => 'paid']))->assertSee('Dette soldée')->assertDontSee('Dette en retard');
        $this->get(route('staff-debts.index', ['employee_id' => $employee->id, 'status' => 'open', 'search' => 'retard']))->assertSee('Dette en retard')->assertDontSee('Dette soldée');
    }

    public function test_money_precision_and_escaping_are_preserved_in_history(): void
    {
        $this->actor();
        $employee = Employee::factory()->create();
        $note = '<script>alert("test")</script>';
        $this->post(route('staff-debts.store'), $this->debtData($employee, ['amount' => '0.30', 'reason' => $note, 'notes' => $note]))->assertSessionHasNoErrors();
        $debt = StaffDebt::sole();
        $this->post(route('staff-debts.repayments.store', $debt), $this->repaymentData(['amount' => '0.10', 'notes' => $note]))->assertSessionHasNoErrors();
        $this->assertSame(20, $debt->fresh()->balance_minor);
        $this->get(route('staff-debts.show', $debt))->assertOk()->assertSee($note)->assertDontSee($note, false)->assertSee('0.20 USD');
    }
}
