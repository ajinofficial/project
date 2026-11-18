<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Support\ActivityNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = $request->user()->tenant_id;
        $editingExpense = null;
        $editingExpenseId = (int) $request->old('expense_id');
        if ($editingExpenseId > 0) {
            $editingExpense = Expense::where('tenant_id', $tenantId)->find($editingExpenseId);
        }
        $perPageOptions = [10, 25, 50, 100];
        $perPage = in_array((int) $request->input('per_page', 10), $perPageOptions, true)
            ? (int) $request->input('per_page', 10) : 10;

        $query = Expense::where('tenant_id', $tenantId)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = (string) $request->string('search');
                $query->where(fn ($query) => $query->where('title', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%"));
            })
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->input('category')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('expense_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('expense_date', '<=', $request->input('to')));

        $filteredTotal = (clone $query)->sum('amount');
        $monthTotal = Expense::where('tenant_id', $tenantId)
            ->whereBetween('expense_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->sum('amount');

        return view('expenses.index', [
            'expenses' => $query->orderByDesc('expense_date')->orderByDesc('id')->paginate($perPage)->withQueryString(),
            'filteredTotal' => $filteredTotal,
            'monthTotal' => $monthTotal,
            'categories' => Expense::CATEGORIES,
            'paymentMethods' => Expense::PAYMENT_METHODS,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'editingExpense' => $editingExpense,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $expense = Expense::create($this->validated($request) + [
            'tenant_id' => $request->user()->tenant_id,
            'created_by' => $request->user()->id,
        ]);

        ActivityNotifier::notify($request->user()->tenant_id, 'expense_created', "Expense recorded",
            $request->user()->name.' recorded '.$expense->title.' for '.$expense->amount.'.',
            parameters: ['actor' => $request->user()->name, 'title' => $expense->title, 'amount' => $expense->amount]
        );

        return back()->with('status', __('orders.expense_recorded_2d913d'));
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorizeTenant($request, $expense);
        $expense->update($this->validated($request));

        return back()->with('status', __('orders.expense_updated_9fe9ff'));
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorizeTenant($request, $expense);
        $expense->delete();

        return back()->with('status', __('orders.expense_deleted_b44fe3'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(Expense::CATEGORIES)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(array_keys(Expense::PAYMENT_METHODS))],
            'reference_number' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'title.required' => __('validation.enter_the_expense_title_a10dca'),
            'category.required' => __('products.select_an_expense_category_056bc5'),
            'amount.required' => __('validation.enter_the_expense_amount_f80e3f'),
            'amount.min' => __('validation.expense_amount_must_be_greater_than_zero_37366b'),
            'expense_date.required' => __('orders.select_the_expense_date_62906b'),
            'payment_method.required' => __('orders.select_a_payment_method_f5e19f'),
        ]);
    }

    private function authorizeTenant(Request $request, Expense $expense): void
    {
        abort_unless((int) $expense->tenant_id === (int) $request->user()->tenant_id, 404);
    }
}
