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

    public function test_expense_form_shows_required_validation_messages_and_hooks(): void
    {
        [$owner] = $this->tenantOwner('validation@example.com');

        $this->actingAs($owner)->post(route('expenses.store'), [
            'title' => '', 'category' => '', 'amount' => '', 'expense_date' => '', 'payment_method' => '',
        ])->assertSessionHasErrors(['title', 'category', 'amount', 'expense_date', 'payment_method']);

        $response = $this->actingAs($owner)->get(route('expenses.index'));

        $response->assertOk();
        $response->assertSee('data-expense-form', false);
        $response->assertSee('data-expense-submit', false);
        $response->assertSee('product-save-button__loading', false);
        $response->assertSee("button.classList.add('is-loading')", false);
        $response->assertSee('Check the expense details');
        $response->assertSee('Enter the expense title.');
        $response->assertSee('Select an expense category.');
        $response->assertSee('Enter the expense amount.');
        $response->assertSee('Select the expense date.');
        $response->assertSee('Select a payment method.');
    }

    public function test_add_and_edit_use_the_same_expense_form(): void
    {
        [$owner, $tenant] = $this->tenantOwner('shared-form@example.com');
        $expense = Expense::create([
            'tenant_id' => $tenant->id, 'created_by' => $owner->id, 'title' => 'Shop rent',
            'category' => 'Rent', 'amount' => 5000, 'expense_date' => '2026-07-20', 'payment_method' => 'cash',
        ]);

        $response = $this->actingAs($owner)->get(route('expenses.index'));

        $response->assertOk()
            ->assertSee('data-update-action-template', false)
            ->assertSee('data-expense-cancel', false)
            ->assertSee('data-expense-edit="'.$expense->id.'"', false)
            ->assertSee('expense-edit-button', false)
            ->assertSee('expense-delete-button', false)
            ->assertSee('data-confirm-title="Delete expense"', false)
            ->assertSee('data-confirm-button="Delete expense"', false)
            ->assertDontSee('window.confirm', false)
            ->assertDontSee('expense-edit-panel', false);
        $this->assertSame(1, preg_match_all('/<form[^>]+data-expense-form(?:\s|>)/', $response->getContent()));

        $this->actingAs($owner)
            ->from(route('expenses.index'))
            ->put(route('expenses.update', $expense), [
                'expense_id' => $expense->id, 'title' => '', 'category' => 'Rent', 'amount' => 5000,
                'expense_date' => '2026-07-20', 'payment_method' => 'cash',
            ])->assertSessionHasErrors('title');

        $this->actingAs($owner)->get(route('expenses.index'))
            ->assertSee('Edit expense')
            ->assertSee(route('expenses.update', $expense), false);
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
