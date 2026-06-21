@extends('layouts.admin', ['title' => 'Customers'])

@section('content')
    <section class="ops-grid">
        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Customer management</p><h2>Add customer</h2></div></div>
            <form class="product-form" method="POST" action="{{ route('customers.store') }}">
                @csrf
                <label><span>Customer name</span><input name="name" required></label>
                <label><span>Mobile number</span><input name="mobile"></label>
                <div class="field-grid">
                    <label><span>Credit limit</span><input type="number" name="credit_limit" min="0" step="0.01" value="0"></label>
                    <label><span>Outstanding balance</span><input type="number" name="outstanding_balance" min="0" step="0.01" value="0"></label>
                </div>
                <button type="submit">Save customer</button>
            </form>
        </article>
        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Sales accounts</p><h2>Customers</h2></div></div>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Name</th><th>Mobile</th><th>Credit limit</th><th>Outstanding</th></tr></thead>
                    <tbody>
                    @forelse ($customers as $customer)
                        <tr><td><strong>{{ $customer->name }}</strong></td><td>{{ $customer->mobile ?: '-' }}</td><td>₹{{ number_format($customer->credit_limit, 2) }}</td><td>₹{{ number_format($customer->outstanding_balance, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="4">No customers yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $customers->links() }}
        </article>
    </section>
@endsection
