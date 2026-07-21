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
        .expense-action-button { display:inline-flex; align-items:center; justify-content:center; gap:6px; min-height:34px; border:1px solid #d8dce5; background:#fff; border-radius:9px; padding:7px 11px; cursor:pointer; color:#30394c; font-weight:600; transition:background .18s ease,border-color .18s ease,transform .18s ease; }
        .expense-action-button:hover { background:#f6f7fa; border-color:#bbc1cd; transform:translateY(-1px); }
        .expense-action-button svg { width:15px; height:15px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
        .expense-edit-button { color:#2855a6; border-color:#cbd9f3; background:#f7faff; }
        .expense-edit-button:hover { background:#edf4ff; border-color:#9db8e8; }
        .expense-delete-button { color:#b42318; border-color:#f0c9c5; background:#fff8f7; }
        .expense-delete-button:hover { color:#981b12; background:#fff0ee; border-color:#e7aaa4; }
        @media(max-width:1100px) { .expense-filters { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media(max-width:800px) { .expense-filters,.expense-form-grid,.expense-stats { grid-template-columns:1fr; } .expense-form-grid .wide { grid-column:auto; } }
    </style>

    <div class="expense-stats" data-expense-stats>
        <div class="expense-stat"><span>This month's expenses</span><strong>&#8377;{{ number_format($monthTotal, 2) }}</strong></div>
        <div class="expense-stat"><span>Filtered total</span><strong>&#8377;{{ number_format($filteredTotal, 2) }}</strong></div>
    </div>

    <section class="ops-grid">
        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Money out</p><h2 data-expense-form-title>{{ $editingExpense ? 'Edit expense' : 'Record expense' }}</h2></div></div>
            <form class="product-form" method="POST" action="{{ $editingExpense ? route('expenses.update', $editingExpense) : route('expenses.store') }}" data-expense-form data-store-action="{{ route('expenses.store') }}" data-update-action-template="{{ route('expenses.update', ['expense' => '__ID__']) }}" data-default-date="{{ now()->toDateString() }}">
                @csrf
                <input type="hidden" name="_method" value="PUT" data-expense-method @disabled(! $editingExpense)>
                <input type="hidden" name="expense_id" value="{{ old('expense_id', $editingExpense?->id) }}" data-expense-id>
                @if ($errors->any())
                    <div class="error-summary" role="alert"><strong>Check the expense details</strong><span>{{ $errors->first() }}</span></div>
                @endif
                <div class="expense-form-grid">
                    <label class="wide"><span>Expense title</span><input type="text" name="title" value="{{ old('title', $editingExpense?->title) }}" maxlength="255" placeholder="e.g. Shop electricity bill" autocomplete="off" required>@error('title')<small>{{ $message }}</small>@enderror</label>
                    <label><span>Category</span><select name="category" required><option value="">Select category</option>@foreach($categories as $category)<option value="{{ $category }}" @selected(old('category', $editingExpense?->category) === $category)>{{ $category }}</option>@endforeach</select>@error('category')<small>{{ $message }}</small>@enderror</label>
                    <label><span>Amount</span><input type="number" name="amount" value="{{ old('amount', $editingExpense?->amount) }}" min="0.01" step="0.01" placeholder="0.00" required>@error('amount')<small>{{ $message }}</small>@enderror</label>
                    <label><span>Expense date</span><input type="date" name="expense_date" value="{{ old('expense_date', $editingExpense?->expense_date?->toDateString() ?? now()->toDateString()) }}" required data-date-picker>@error('expense_date')<small>{{ $message }}</small>@enderror</label>
                    <label><span>Payment method</span><select name="payment_method" required>@foreach($paymentMethods as $value => $label)<option value="{{ $value }}" @selected(old('payment_method', $editingExpense?->payment_method ?? 'cash') === $value)>{{ $label }}</option>@endforeach</select>@error('payment_method')<small>{{ $message }}</small>@enderror</label>
                    <label class="wide"><span>Reference number </span><input type="text" name="reference_number" value="{{ old('reference_number', $editingExpense?->reference_number) }}" maxlength="120" placeholder="Receipt, transaction or cheque number" autocomplete="off"></label>
                    <label class="wide"><span>Notes </span><textarea name="notes" rows="3" maxlength="2000" placeholder="Add any useful details">{{ old('notes', $editingExpense?->notes) }}</textarea></label>
                </div>
                <div class="expense-actions">
                    <button class="product-save-button" type="submit" data-expense-submit><span class="product-save-button__idle">{{ $editingExpense ? 'Update expense' : 'Save expense' }}</span><span class="product-save-button__loading" aria-hidden="true"><i></i>{{ $editingExpense ? 'Updating' : 'Saving' }}</span></button>
                    <button class="expense-action-button" type="button" data-expense-cancel @hidden(! $editingExpense)>Cancel</button>
                </div>
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
                        <td><div class="expense-actions"><button type="button" class="expense-action-button expense-edit-button" data-expense-edit="{{ $expense->id }}" data-title="{{ $expense->title }}" data-category="{{ $expense->category }}" data-amount="{{ $expense->amount }}" data-date="{{ $expense->expense_date->toDateString() }}" data-payment="{{ $expense->payment_method }}" data-reference="{{ $expense->reference_number }}" data-notes="{{ $expense->notes }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>Edit</button><form method="POST" action="{{ route('expenses.destroy', $expense) }}" data-confirm data-confirm-title="Delete expense" data-confirm-message="Are you sure you want to delete {{ $expense->title }}? This action cannot be undone." data-confirm-button="Delete expense">@csrf @method('DELETE')<button class="expense-action-button expense-delete-button" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>Delete</button></form></div></td>
                    </tr>
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
                        var form = document.querySelector('[data-expense-form]');
                        var method = form.querySelector('[data-expense-method]');
                        form.action = form.dataset.updateActionTemplate.replace('__ID__', button.dataset.expenseEdit);
                        method.disabled = false;
                        form.querySelector('[data-expense-id]').value = button.dataset.expenseEdit;
                        form.elements.title.value = button.dataset.title || '';
                        form.elements.category.value = button.dataset.category || '';
                        form.elements.amount.value = button.dataset.amount || '';
                        form.elements.expense_date.value = button.dataset.date || '';
                        form.elements.payment_method.value = button.dataset.payment || 'cash';
                        form.elements.reference_number.value = button.dataset.reference || '';
                        form.elements.notes.value = button.dataset.notes || '';
                        document.querySelector('[data-expense-form-title]').textContent = 'Edit expense';
                        form.querySelector('.product-save-button__idle').textContent = 'Update expense';
                        form.querySelector('.product-save-button__loading').lastChild.textContent = 'Updating';
                        form.querySelector('[data-expense-cancel]').hidden = false;
                        form.querySelectorAll('[data-validation-error]').forEach(function (error) { error.remove(); });
                        form.querySelectorAll('[aria-invalid]').forEach(function (field) { field.removeAttribute('aria-invalid'); });
                        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        form.elements.title.focus({ preventScroll: true });
                    });
                });

            }

            var expenseCancel = document.querySelector('[data-expense-cancel]');
            if (expenseCancel) {
                expenseCancel.addEventListener('click', function () {
                    var form = document.querySelector('[data-expense-form]');
                    form.action = form.dataset.storeAction;
                    form.querySelector('[data-expense-method]').disabled = true;
                    form.querySelector('[data-expense-id]').value = '';
                    form.elements.title.value = '';
                    form.elements.category.value = '';
                    form.elements.amount.value = '';
                    form.elements.expense_date.value = form.dataset.defaultDate;
                    form.elements.payment_method.value = 'cash';
                    form.elements.reference_number.value = '';
                    form.elements.notes.value = '';
                    document.querySelector('[data-expense-form-title]').textContent = 'Record expense';
                    form.querySelector('.product-save-button__idle').textContent = 'Save expense';
                    form.querySelector('.product-save-button__loading').lastChild.textContent = 'Saving';
                    expenseCancel.hidden = true;
                    form.querySelectorAll('[data-validation-error]').forEach(function (error) { error.remove(); });
                    form.querySelectorAll('[aria-invalid]').forEach(function (field) { field.removeAttribute('aria-invalid'); });
                    form.elements.title.focus();
                });
            }

            function bindExpenseValidation(scope) {
                scope.querySelectorAll('[data-expense-form]').forEach(function (form) {
                    if (form.dataset.validationBound) return;
                    form.dataset.validationBound = 'true';
                    form.noValidate = true;

                    function validateField(field) {
                        var error = field.nextElementSibling && field.nextElementSibling.matches('[data-validation-error]')
                            ? field.nextElementSibling : null;

                        if (!field.willValidate || field.checkValidity()) {
                            if (error) error.remove();
                            field.removeAttribute('aria-invalid');
                            return true;
                        }

                        if (!error) {
                            error = document.createElement('small');
                            error.setAttribute('data-validation-error', '');
                            error.setAttribute('role', 'alert');
                            field.insertAdjacentElement('afterend', error);
                        }

                        var label = field.closest('label');
                        var labelText = label && label.querySelector('span');
                        error.textContent = field.validity.valueMissing
                            ? (labelText ? labelText.textContent.trim() : 'This field') + ' is required.'
                            : field.validationMessage;
                        field.setAttribute('aria-invalid', 'true');
                        return false;
                    }

                    form.querySelectorAll('input, select, textarea').forEach(function (field) {
                        field.addEventListener('input', function () { validateField(field); });
                        field.addEventListener('change', function () { validateField(field); });
                    });

                    form.addEventListener('submit', function (event) {
                        var firstInvalid = null;
                        var button = form.querySelector('[data-expense-submit]');
                        form.querySelectorAll('input, select, textarea').forEach(function (field) {
                            if (!validateField(field) && !firstInvalid) firstInvalid = field;
                        });
                        if (firstInvalid) {
                            event.preventDefault();
                            firstInvalid.focus();
                            return;
                        }
                        if (button) {
                            button.disabled = true;
                            button.classList.add('is-loading');
                            button.setAttribute('aria-busy', 'true');
                        }
                    });
                });
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
                        bindExpenseValidation(history);
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
            bindExpenseValidation(document);
            bindFilters();
        });
    </script>
@endsection
