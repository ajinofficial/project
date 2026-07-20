<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_view_update_and_delete_an_expense(): void
    {
        [$owner, $tenant] = $this->tenantOwner('owner@example.com');

        $this->actingAs($owner)->post(route('expenses.store'), [
            'title' => 'Electricity bill', 'category' => 'Utilities', 'amount' => '1250.50',
            'expense_date' => '2026-07-20', 'payment_method' => 'upi',
            'reference_number' => 'TXN-101', 'notes' => 'July bill',
        ])->assertSessionHas('status', 'Expense recorded.');

        $expense = Expense::firstOrFail();
        $this->assertSame($tenant->id, $expense->tenant_id);
        $this->actingAs($owner)->get(route('expenses.index'))->assertOk()
            ->assertSee('Electricity bill')->assertSee('1,250.50')
            ->assertSee('type="text" name="title"', false)
            ->assertSee('type="text" name="reference_number"', false);

        $this->actingAs($owner)->put(route('expenses.update', $expense), [
            'title' => 'Power bill', 'category' => 'Utilities', 'amount' => '1300',
            'expense_date' => '2026-07-20', 'payment_method' => 'cash',
        ])->assertSessionHas('status', 'Expense updated.');
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'title' => 'Power bill', 'amount' => 1300]);

        $this->actingAs($owner)->delete(route('expenses.destroy', $expense))->assertSessionHas('status', 'Expense deleted.');
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_expenses_are_tenant_scoped_and_cannot_be_changed_cross_tenant(): void
    {
        [$owner, $tenant] = $this->tenantOwner('first@example.com');
        [$otherOwner, $otherTenant] = $this->tenantOwner('second@example.com');
        $otherExpense = Expense::create([
            'tenant_id' => $otherTenant->id, 'created_by' => $otherOwner->id, 'title' => 'Private expense',
            'category' => 'Other', 'amount' => 50, 'expense_date' => '2026-07-20', 'payment_method' => 'cash',
        ]);

        $this->actingAs($owner)->get(route('expenses.index'))->assertDontSee('Private expense');
        $this->actingAs($owner)->delete(route('expenses.destroy', $otherExpense))->assertNotFound();
        $this->assertDatabaseHas('expenses', ['id' => $otherExpense->id, 'tenant_id' => $otherTenant->id]);
    }

    public function test_expense_validation_and_filters_work(): void
    {
        [$owner, $tenant] = $this->tenantOwner('filters@example.com');
        Expense::create(['tenant_id' => $tenant->id, 'created_by' => $owner->id, 'title' => 'Office rent', 'category' => 'Rent', 'amount' => 5000, 'expense_date' => '2026-07-01', 'payment_method' => 'bank_transfer']);
        Expense::create(['tenant_id' => $tenant->id, 'created_by' => $owner->id, 'title' => 'Online ads', 'category' => 'Marketing', 'amount' => 750, 'expense_date' => '2026-06-01', 'payment_method' => 'card']);

        $this->actingAs($owner)->get(route('expenses.index', ['category' => 'Rent', 'from' => '2026-07-01']))
            ->assertSee('Office rent')->assertDontSee('Online ads')
            ->assertSee('data-expense-filter-form', false)
            ->assertSee('fetch(url', false)
            ->assertDontSee('>Filter</button>', false)
            ->assertViewHas('filteredTotal', fn ($total) => (float) $total === 5000.0);

        $this->actingAs($owner)->post(route('expenses.store'), [
            'title' => '', 'category' => 'Invalid', 'amount' => 0, 'expense_date' => '', 'payment_method' => 'coins',
        ])->assertSessionHasErrors(['title', 'category', 'amount', 'expense_date', 'payment_method']);
    }

    private function tenantOwner(string $email): array
    {
        $plan = Plan::where('name', 'starter')->firstOrFail();
        $tenant = Tenant::create([
            'plan_id' => $plan->id, 'tenant_type' => Tenant::TYPE_CLIENT, 'business_name' => 'Demo Store',
            'owner_name' => 'Owner', 'mobile' => '9999999999', 'email' => $email,
            'business_category' => Tenant::CATEGORY_RETAIL, 'store_address' => 'Demo road',
            'role_permissions' => RolePermission::defaults(),
        ]);
        $owner = User::create([
            'tenant_id' => $tenant->id, 'name' => 'Owner', 'email' => $email, 'company_name' => 'Demo Store',
            'phone' => '9999999999', 'plan' => $plan->id, 'role' => User::ROLE_OWNER, 'password' => 'Password123',
        ]);

        return [$owner, $tenant];
    }
}
