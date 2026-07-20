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
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $expense = Expense::create($this->validated($request) + [
            'tenant_id' => $request->user()->tenant_id,
            'created_by' => $request->user()->id,
        ]);

        ActivityNotifier::notify($request->user()->tenant_id, 'expense_created', 'Expense recorded',
            $request->user()->name.' recorded '.$expense->title.' for '.$expense->amount.'.');

        return back()->with('status', 'Expense recorded.');
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorizeTenant($request, $expense);
        $expense->update($this->validated($request));

        return back()->with('status', 'Expense updated.');
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorizeTenant($request, $expense);
        $expense->delete();

        return back()->with('status', 'Expense deleted.');
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
        ]);
    }

    private function authorizeTenant(Request $request, Expense $expense): void
    {
        abort_unless((int) $expense->tenant_id === (int) $request->user()->tenant_id, 404);
    }
}
