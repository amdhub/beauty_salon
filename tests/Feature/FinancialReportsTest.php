<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SalonVisit;
use App\Models\StaffDebt;
use App\Models\StaffRepayment;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\FinancialReport;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FinancialReportsTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role = 'manager'): User
    {
        $this->seed(RolePermissionSeeder::class);
        $actor = User::factory()->create();
        $actor->roles()->attach(Role::where('slug', $role)->sole());
        $this->actingAs($actor);
        $this->travelTo(now()->setDate(2030, 1, 15)->setTime(12, 0));
        config(['salon.currency' => 'USD']);

        return $actor;
    }

    private function fixtures(): void
    {
        $visit = SalonVisit::factory()->create(['total' => '1000.00', 'created_at' => '2029-12-01 10:00:00']);
        Payment::factory()->create(['salon_visit_id' => $visit->id, 'amount' => '105.00', 'payment_date' => '2030-01-09 23:59:59']);
        Expense::factory()->create(['amount_minor' => 2000, 'paid_on' => '2030-01-09']);
        Payment::factory()->create(['salon_visit_id' => $visit->id, 'amount' => '50.00', 'payment_date' => '2030-01-11 23:59:59']);
        Payment::factory()->create(['salon_visit_id' => $visit->id, 'amount' => '30.00', 'payment_method' => 'mpesa', 'payment_date' => '2030-01-10 00:00:00']);
        StaffDebt::factory()->create(['amount_minor' => 2000, 'issued_on' => '2030-01-10']);
        $loan = StaffDebt::factory()->create(['amount_minor' => 500, 'repaid_minor' => 500, 'issued_on' => '2030-01-09']);
        StaffRepayment::factory()->create(['staff_debt_id' => $loan->id, 'amount_minor' => 500, 'paid_on' => '2030-01-11']);
        Expense::factory()->create(['amount_minor' => 1000, 'paid_on' => '2030-01-10']);
        Expense::factory()->create(['amount_minor' => 300, 'payment_method' => 'mpesa', 'paid_on' => '2030-01-11']);
        Payment::factory()->create(['amount' => '999.00', 'payment_date' => '2030-01-12 00:00:00']);
        SalonVisit::factory()->create(['total' => '9999.00', 'created_at' => '2030-01-10 10:00:00']);
        StockMovement::factory()->create(['type' => 'supply', 'total_cost' => '200.00', 'movement_date' => '2030-01-10']);
    }

    public function test_report_uses_actual_payment_dates_and_separates_opening_period_and_closing_balances(): void
    {
        $this->actor();
        $this->fixtures();
        $response = $this->get(route('financial-reports.index', ['from' => '2030-01-10', 'to' => '2030-01-11', 'currency' => 'USD']))->assertOk();
        $total = $response->viewData('totals')->sole();
        $this->assertSame(8000, $total['opening_minor']);
        $this->assertSame(8500, $total['income_minor']);
        $this->assertSame(3300, $total['outgoing_minor']);
        $this->assertSame(5200, $total['net_minor']);
        $this->assertSame(13200, $total['closing_minor']);
        $this->assertSame(8000, $total['sales_minor']);
        $this->assertSame(1300, $total['expenses_minor']);
        $this->assertSame(2000, $total['loans_minor']);
        $this->assertSame(500, $total['repayments_minor']);
        $this->assertSame(6, $response->viewData('movements')->total());
        $accounts = $response->viewData('accounts')->keyBy('payment_method');
        $this->assertSame(10500, $accounts['cash']['closing_minor']);
        $this->assertSame(2700, $accounts['mpesa']['closing_minor']);
    }

    public function test_cash_filter_applies_to_opening_totals_and_transactions(): void
    {
        $this->actor();
        $this->fixtures();
        $response = $this->get(route('financial-reports.index', ['from' => '2030-01-10', 'to' => '2030-01-11', 'currency' => 'USD', 'payment_method' => 'cash']))->assertOk();
        $total = $response->viewData('totals')->sole();
        $this->assertSame(8000, $total['opening_minor']);
        $this->assertSame(5500, $total['income_minor']);
        $this->assertSame(3000, $total['outgoing_minor']);
        $this->assertSame(10500, $total['closing_minor']);
        $this->assertSame(4, $response->viewData('movements')->total());
        $this->assertSame(['cash'], $response->viewData('movements')->pluck('payment_method')->unique()->values()->all());
    }

    public function test_currencies_are_not_added_together_and_negative_balances_are_visible(): void
    {
        $this->actor();
        Payment::factory()->create(['amount' => '10.00', 'payment_date' => '2030-01-10']);
        Expense::factory()->create(['currency' => 'CDF', 'amount_minor' => 100000, 'paid_on' => '2030-01-10']);
        $response = $this->get(route('financial-reports.index', ['from' => '2030-01-10', 'to' => '2030-01-10']))->assertOk();
        $totals = $response->viewData('totals')->keyBy('currency');
        $this->assertCount(2, $totals);
        $this->assertSame(1000, $totals['USD']['closing_minor']);
        $this->assertSame(-100000, $totals['CDF']['closing_minor']);
        $response->assertSee('-1000.00');
    }

    public function test_totals_are_not_limited_to_the_current_page_and_fractional_cents_are_exact(): void
    {
        $this->actor();
        $visit = SalonVisit::factory()->create();
        $actor = User::factory()->create();
        Payment::factory()->count(26)->create(['salon_visit_id' => $visit->id, 'received_by' => $actor->id, 'amount' => '0.10', 'payment_date' => '2030-01-10']);
        Expense::factory()->create(['amount_minor' => 30, 'paid_on' => '2030-01-10']);
        $response = $this->get(route('financial-reports.index', ['from' => '2030-01-10', 'to' => '2030-01-10', 'page' => 2]))->assertOk();
        $this->assertSame(230, $response->viewData('totals')->sole()['closing_minor']);
        $this->assertSame(27, $response->viewData('movements')->total());
        $this->assertSame(2, $response->viewData('movements')->count());
    }

    public function test_a_period_without_movements_preserves_the_previous_balance_and_empty_report_is_zero(): void
    {
        $this->actor();
        $this->get(route('financial-reports.index'))->assertOk()->assertViewHas('filters', fn ($filters) => $filters['from'] === '2030-01-01' && $filters['to'] === '2030-01-15')
            ->assertViewHas('totals', fn ($totals) => $totals->sole()['closing_minor'] === 0);
        Payment::factory()->create(['amount' => '10.00', 'payment_date' => '2030-01-01']);
        $response = $this->get(route('financial-reports.index', ['from' => '2030-01-10', 'to' => '2030-01-11']))->assertOk();
        $this->assertSame(1000, $response->viewData('totals')->sole()['opening_minor']);
        $this->assertSame(1000, $response->viewData('totals')->sole()['closing_minor']);
        $this->assertSame(0, $response->viewData('movements')->total());
    }

    public function test_legacy_loan_method_can_be_completed_once_and_changes_the_cash_filter(): void
    {
        $this->actor();
        $debt = StaffDebt::factory()->create(['payment_method' => null, 'amount_minor' => 2500, 'issued_on' => '2030-01-10']);
        $filters = ['from' => '2030-01-10', 'to' => '2030-01-10'];
        $response = $this->get(route('financial-reports.index', $filters))->assertOk()->assertViewHas('hasUnknownMethods', true);
        $this->assertSame(-2500, $response->viewData('totals')->sole()['closing_minor']);
        $this->get(route('financial-reports.index', [...$filters, 'payment_method' => 'cash']))->assertViewHas('totals', fn ($totals) => $totals->sole()['closing_minor'] === 0);
        $this->get(route('financial-reports.index', [...$filters, 'payment_method' => 'unknown']))->assertViewHas('totals', fn ($totals) => $totals->sole()['closing_minor'] === -2500);
        $this->get(route('staff-debts.show', $debt))->assertSee('Compléter le mode de versement initial');
        $this->patch(route('staff-debts.payment-method.update', $debt), ['payment_method' => 'cash', 'amount_minor' => 1])->assertSessionHasNoErrors();
        $this->assertSame(2500, $debt->fresh()->amount_minor);
        $this->get(route('financial-reports.index', [...$filters, 'payment_method' => 'cash']))->assertViewHas('hasUnknownMethods', false)->assertViewHas('totals', fn ($totals) => $totals->sole()['closing_minor'] === -2500);
        $this->patch(route('staff-debts.payment-method.update', $debt), ['payment_method' => 'bank'])->assertSessionHasErrors('payment_method');
        $this->assertSame('cash', $debt->fresh()->payment_method->value);
    }

    public function test_new_loans_require_and_preserve_the_disbursement_method(): void
    {
        $this->actor();
        $employee = Employee::factory()->create();
        $data = ['employee_id' => $employee->id, 'amount' => '10.00', 'issued_on' => '2030-01-10', 'reason' => 'Avance', 'idempotency_key' => (string) Str::uuid()];
        $this->post(route('staff-debts.store'), $data)->assertSessionHasErrors('payment_method');
        $this->post(route('staff-debts.store'), [...$data, 'payment_method' => 'mpesa'])->assertSessionHasNoErrors();
        $this->assertSame('mpesa', StaffDebt::sole()->payment_method->value);
    }

    public static function roles(): array
    {
        return [['administrator', true], ['manager', true], ['receptionist', false], ['beautician', false]];
    }

    #[DataProvider('roles')]
    public function test_financial_reports_require_their_own_permission(string $role, bool $allowed): void
    {
        $actor = $this->actor($role);
        $this->assertSame($allowed, $actor->can('view', FinancialReport::class));
        $this->get(route('financial-reports.index'))->assertStatus($allowed ? 200 : 403);
        if (! $allowed) {
            $debt = StaffDebt::factory()->create(['payment_method' => null]);
            $this->patch(route('staff-debts.payment-method.update', $debt), ['payment_method' => 'cash'])->assertForbidden();
            $this->assertNull($debt->fresh()->payment_method);
        }
    }

    public function test_guest_cannot_view_reports(): void
    {
        $this->get(route('financial-reports.index'))->assertRedirect(route('login'));
    }

    public function test_report_only_permission_does_not_grant_source_record_access(): void
    {
        $actor = $this->actor();
        $role = Role::create(['name' => 'Rapports', 'slug' => 'reports-only']);
        $role->permissions()->attach(Permission::where('slug', 'reports.financial')->sole());
        $actor->roles()->sync([$role->id]);
        $actor->unsetRelation('roles');
        $expense = Expense::factory()->create();
        $this->get(route('financial-reports.index'))->assertOk()->assertDontSee(route('expenses.show', $expense));
        $this->get(route('expenses.show', $expense))->assertForbidden();
    }

    public static function invalidFilters(): array
    {
        return [
            'reversed dates' => [['from' => '2030-01-11', 'to' => '2030-01-10'], 'to'],
            'bad date' => [['from' => '2030-02-31'], 'from'],
            'bad method' => [['payment_method' => 'random'], 'payment_method'],
            'array currency' => [['currency' => ['USD']], 'currency'],
        ];
    }

    #[DataProvider('invalidFilters')]
    public function test_invalid_filters_are_rejected(array $filters, string $field): void
    {
        $this->actor();
        $this->get(route('financial-reports.index', $filters))->assertSessionHasErrors($field);
    }
}
