@extends('layouts.admin', ['title' => 'Expenses'])

@section('content')
    <style>
        .expense-stats { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; margin-bottom:18px; }
        .expense-stat { padding:18px 20px; border:1px solid #e7e9ef; border-radius:14px; background:#fff; }
        .expense-stat span { display:block; color:#717784; font-size:13px; margin-bottom:5px; }
        .expense-stat strong { font-size:24px; color:#172033; }
        .expense-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
        .expense-form-grid .wide { grid-column:1/-1; }
        .expense-filters { display:grid; grid-template-columns:minmax(150px,1fr) 135px 135px 105px auto; gap:8px; align-items:center; }
        .expense-filters input,
        .expense-filters select { min-height:40px; padding:8px 10px; font-size:13px; }
        .expense-filters .expense-action-button,
        .expense-filters .product-clear-filter { min-height:40px; padding:9px 12px; white-space:nowrap; }
        .expense-actions { display:flex; gap:8px; align-items:center; }
        .expense-edit-panel { display:none; margin-top:12px; padding:16px; border-radius:12px; background:#f7f8fb; }
        .expense-edit-panel.is-open { display:block; }
        .expense-action-button { border:1px solid #d8dce5; background:#fff; border-radius:8px; padding:7px 10px; cursor:pointer; color:#30394c; }
        .expense-delete-button { color:#b42318; }
        @media(max-width:1100px) { .expense-filters { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media(max-width:800px) { .expense-filters,.expense-form-grid,.expense-stats { grid-template-columns:1fr; } .expense-form-grid .wide { grid-column:auto; } }
    </style>

    <div class="expense-stats" data-expense-stats>
        <div class="expense-stat"><span>This month's expenses</span><strong>&#8377;{{ number_format($monthTotal, 2) }}</strong></div>
        <div class="expense-stat"><span>Filtered total</span><strong>&#8377;{{ number_format($filteredTotal, 2) }}</strong></div>
    </div>

    <section class="ops-grid">
        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Money out</p><h2>Record expense</h2></div></div>
            <form class="product-form" method="POST" action="{{ route('expenses.store') }}">
                @csrf
                @if ($errors->any())
                    <div class="error-summary" role="alert"><strong>Check the expense details</strong><span>{{ $errors->first() }}</span></div>
                @endif
                <div class="expense-form-grid">
                    <label class="wide"><span>Expense title</span><input type="text" name="title" value="{{ old('title') }}" maxlength="255" placeholder="e.g. Shop electricity bill" autocomplete="off" required>@error('title')<small>{{ $message }}</small>@enderror</label>
                    <label><span>Category</span><select name="category" required><option value="">Select category</option>@foreach($categories as $category)<option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>@endforeach</select>@error('category')<small>{{ $message }}</small>@enderror</label>
                    <label><span>Amount</span><input type="number" name="amount" value="{{ old('amount') }}" min="0.01" step="0.01" placeholder="0.00" required>@error('amount')<small>{{ $message }}</small>@enderror</label>
                    <label><span>Expense date</span><input type="date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" required data-date-picker>@error('expense_date')<small>{{ $message }}</small>@enderror</label>
                    <label><span>Payment method</span><select name="payment_method" required>@foreach($paymentMethods as $value => $label)<option value="{{ $value }}" @selected(old('payment_method', 'cash') === $value)>{{ $label }}</option>@endforeach</select></label>
                    <label class="wide"><span>Reference number </span><input type="text" name="reference_number" value="{{ old('reference_number') }}" maxlength="120" placeholder="Receipt, transaction or cheque number" autocomplete="off"></label>
                    <label class="wide"><span>Notes </span><textarea name="notes" rows="3" maxlength="2000" placeholder="Add any useful details">{{ old('notes') }}</textarea></label>
                </div>
                <button class="product-save-button" type="submit">Save expense</button>
            </form>
        </article>

        <article class="admin-section" data-expense-history>
            <div class="section-title"><div><p class="eyebrow">Expense history</p><h2>All expenses</h2></div></div>
            <div class="product-toolbar">
                <form class="expense-filters" method="GET" action="{{ route('expenses.index') }}" data-expense-filter-form>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search title or reference">
                    <input type="date" name="from" value="{{ request('from') }}" aria-label="From date" title="From date">
                    <input type="date" name="to" value="{{ request('to') }}" aria-label="To date" title="To date">
                    <select name="per_page" aria-label="Expenses per page">@foreach($perPageOptions as $option)<option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} / page</option>@endforeach</select>
                    <a class="product-clear-filter" href="{{ route('expenses.index') }}">Clear</a>
                </form>
            </div>

            <div class="table-wrap"><table class="admin-table"><thead><tr><th>Date</th><th>Expense</th><th>Category</th><th>Payment</th><th>Amount</th><th>Actions</th></tr></thead><tbody>
                @forelse($expenses as $expense)
                    <tr>
                        <td>{{ $expense->expense_date->format('d M Y') }}</td>
                        <td><strong>{{ $expense->title }}</strong>@if($expense->reference_number)<br><small>Ref: {{ $expense->reference_number }}</small>@endif</td>
                        <td>{{ $expense->category }}</td><td>{{ $expense->payment_method_label }}</td><td>&#8377;{{ number_format($expense->amount, 2) }}</td>
                        <td><div class="expense-actions"><button type="button" class="expense-action-button" data-expense-edit="{{ $expense->id }}">Edit</button><form method="POST" action="{{ route('expenses.destroy', $expense) }}" data-confirm data-confirm-title="Delete expense" data-confirm-message="Delete {{ $expense->title }}? This cannot be undone." data-confirm-button="Delete">@csrf @method('DELETE')<button class="expense-action-button expense-delete-button" type="submit">Delete</button></form></div></td>
                    </tr>
                    <tr><td colspan="6" style="padding:0;border:0"><div class="expense-edit-panel" data-expense-panel="{{ $expense->id }}">
                        <form class="product-form expense-form-grid" method="POST" action="{{ route('expenses.update', $expense) }}">@csrf @method('PUT')
                            <label class="wide"><span>Expense title</span><input type="text" name="title" value="{{ $expense->title }}" maxlength="255" autocomplete="off" required></label>
                            <label><span>Category</span><select name="category" required>@foreach($categories as $category)<option value="{{ $category }}" @selected($expense->category === $category)>{{ $category }}</option>@endforeach</select></label>
                            <label><span>Amount</span><input type="number" name="amount" value="{{ $expense->amount }}" min="0.01" step="0.01" required></label>
                            <label><span>Expense date</span><input type="date" name="expense_date" value="{{ $expense->expense_date->toDateString() }}" required></label>
                            <label><span>Payment method</span><select name="payment_method">@foreach($paymentMethods as $value => $label)<option value="{{ $value }}" @selected($expense->payment_method === $value)>{{ $label }}</option>@endforeach</select></label>
                            <label class="wide"><span>Reference number</span><input type="text" name="reference_number" value="{{ $expense->reference_number }}" maxlength="120" autocomplete="off"></label>
                            <label class="wide"><span>Notes</span><textarea name="notes" rows="2" maxlength="2000">{{ $expense->notes }}</textarea></label>
                            <div class="expense-actions wide"><button class="product-save-button" type="submit">Update expense</button><button class="expense-action-button" type="button" data-expense-edit="{{ $expense->id }}">Cancel</button></div>
                        </form>
                    </div></td></tr>
                @empty
                    <tr><td colspan="6">{{ request()->hasAny(['search','from','to']) ? 'No expenses match the current filters.' : 'No expenses recorded yet.' }}</td></tr>
                @endforelse
            </tbody></table></div>
            @include('products.partials.pagination', ['paginator' => $expenses, 'itemLabel' => 'expenses'])
        </article>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var searchTimer;
            var activeRequest;

            function bindExpenseActions(scope, ajaxLoaded) {
                scope.querySelectorAll('[data-expense-edit]').forEach(function (button) {
                    if (button.dataset.expenseBound) return;
                    button.dataset.expenseBound = 'true';
                button.addEventListener('click', function () {
                    var panel = document.querySelector('[data-expense-panel="' + button.dataset.expenseEdit + '"]');
                    if (panel) panel.classList.toggle('is-open');
                });
                });

                if (ajaxLoaded) {
                    scope.querySelectorAll('form[data-confirm]').forEach(function (form) {
                        form.addEventListener('submit', function (event) {
                            if (!window.confirm(form.dataset.confirmMessage || 'Delete this expense?')) event.preventDefault();
                        });
                    });
                }
            }

            function bindFilters() {
                var form = document.querySelector('[data-expense-filter-form]');
                if (!form) return;

                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    loadExpenses(new URLSearchParams(new FormData(form)).toString());
                });

                form.querySelectorAll('select, input[type="date"]').forEach(function (field) {
                    field.addEventListener('change', function () { form.requestSubmit(); });
                });

                var search = form.querySelector('input[type="search"]');
                search.addEventListener('input', function () {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(function () { form.requestSubmit(); }, 350);
                });

                form.querySelector('.product-clear-filter').addEventListener('click', function (event) {
                    event.preventDefault();
                    loadExpenses('');
                });

                document.querySelectorAll('[data-expense-history] .product-pagination a').forEach(function (link) {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        loadExpenses(new URL(link.href).searchParams.toString());
                    });
                });
            }

            function loadExpenses(query, updateUrl) {
                if (typeof updateUrl === 'undefined') updateUrl = true;
                if (activeRequest) activeRequest.abort();
                activeRequest = new AbortController();
                var url = '{{ route('expenses.index') }}' + (query ? '?' + query : '');
                var history = document.querySelector('[data-expense-history]');
                history.style.opacity = '.55';
                history.setAttribute('aria-busy', 'true');

                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: activeRequest.signal })
                    .then(function (response) { if (!response.ok) throw new Error('Request failed'); return response.text(); })
                    .then(function (html) {
                        var page = new DOMParser().parseFromString(html, 'text/html');
                        document.querySelector('[data-expense-stats]').innerHTML = page.querySelector('[data-expense-stats]').innerHTML;
                        history.innerHTML = page.querySelector('[data-expense-history]').innerHTML;
                        history.style.opacity = '';
                        history.removeAttribute('aria-busy');
                        if (updateUrl) window.history.pushState({}, '', url);
                        bindExpenseActions(history, true);
                        bindFilters();
                    })
                    .catch(function (error) {
                        if (error.name !== 'AbortError') window.location.assign(url);
                    });
            }

            window.addEventListener('popstate', function () {
                loadExpenses(window.location.search.replace(/^\?/, ''), false);
            });

            bindExpenseActions(document, false);
            bindFilters();
        });
    </script>
@endsection
