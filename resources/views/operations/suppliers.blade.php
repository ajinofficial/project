@extends('layouts.admin', ['title' => 'Suppliers'])

@section('content')
    <section class="ops-grid">
        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Supplier management</p><h2>Add supplier</h2></div></div>
            <form class="product-form" method="POST" action="{{ route('suppliers.store') }}">
                @csrf
                <label><span>Supplier name</span><input name="name" required></label>
                <label><span>Contact information</span><input name="contact_information"></label>
                <div class="field-grid">
                    <label><span>GST number</span><input name="gst_number"></label>
                    <label><span>Payment terms</span><input name="payment_terms" placeholder="Net 15, COD"></label>
                </div>
                <label><span>Outstanding balance</span><input type="number" name="outstanding_balance" min="0" step="0.01" value="0"></label>
                <button type="submit">Save supplier</button>
            </form>
        </article>
        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Purchase partners</p><h2>Suppliers</h2></div></div>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Name</th><th>GST</th><th>Terms</th><th>Outstanding</th></tr></thead>
                    <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr><td><strong>{{ $supplier->name }}</strong><span>{{ $supplier->contact_information }}</span></td><td>{{ $supplier->gst_number ?: '-' }}</td><td>{{ $supplier->payment_terms ?: '-' }}</td><td>₹{{ number_format($supplier->outstanding_balance, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="4">No suppliers yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $suppliers->links() }}
        </article>
    </section>
@endsection
