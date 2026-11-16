<?php

namespace App\Http\Controllers;

use App\Models\{Customer, Product, ServiceOrder, StockMovement, User};
use App\Support\StockNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\{Rule, ValidationException};

class ServiceOrderController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant_id;
        $query = ServiceOrder::where('tenant_id', $tenant);
        $orders = (clone $query)->with('customer', 'technician', 'costs')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($q) => $q->where('service_type', 'like', $term)->orWhereHas('customer', fn ($q) => $q->where('name', 'like', $term)));
            })->latest()->paginate(15)->withQueryString();
        $completed = (clone $query)->where('status', 'completed')->with('costs')->get();
        return view('services.index', [
            'orders' => $orders,
            'customers' => Customer::where('tenant_id', $tenant)->orderBy('name')->get(),
            'revenue' => $completed->sum('charge'), 'cost' => $completed->sum('total_cost'),
            'outstanding' => (clone $query)->selectRaw('COALESCE(SUM(charge - paid_amount), 0) as total')->value('total'),
            'open' => (clone $query)->where('status', '!=', 'completed')->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->details($request);
        $order = ServiceOrder::create($data + ['tenant_id' => $request->user()->tenant_id]);
        if ($request->expectsJson()) {
            $request->session()->flash('status', 'Service order created.');
            return response()->json(['message' => 'Service order created.', 'redirect' => route('services.show', $order)], 201);
        }
        return redirect()->route('services.show', $order)->with('status', 'Service order created.');
    }

    public function show(Request $request, int $service)
    {
        $order = $this->order($request, $service)->load('customer', 'technician', 'costs');
        return view('services.show', [
            'order' => $order,
            'payments' => DB::table('payments')->where('tenant_id', $request->user()->tenant_id)->where('payable_type', ServiceOrder::class)->where('payable_id', $order->id)->latest()->get(),
        ]);
    }

    public function update(Request $request, int $service)
    {
        $data = $request->validate([
            'technician_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['required', Rule::in(ServiceOrder::STATUSES)],
        ]);
        DB::transaction(function () use ($request, $service, $data) {
            $order = $this->order($request, $service, true);
            $order->update($data + ['completed_at' => $data['status'] === 'completed' ? ($order->completed_at ?? now()) : null]);
        });
        return back()->with('status', 'Service order updated.');
    }

    public function cost(Request $request, int $service)
    {
        $data = $request->validate([
            'kind' => ['required', 'in:part,expense'],
            'product_id' => ['required_if:kind,part', 'nullable', Rule::exists('products', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'description' => ['required_if:kind,expense', 'nullable', 'string', 'max:255'],
            'unit_cost' => ['required_if:kind,expense', 'nullable', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
        ], [
            'description.required_if' => 'Please enter an expense description.',
            'quantity.required' => 'Please enter a quantity.',
            'unit_cost.required_if' => 'Please enter the unit cost.',
        ]);
        DB::transaction(function () use ($request, $service, $data) {
            $order = $this->order($request, $service, true);
            if ($order->status === 'completed') {
                throw ValidationException::withMessages(['kind' => 'Reopen this order before adding costs.']);
            }
            $cost = ['description' => $data['description'] ?? '', 'quantity' => $data['quantity'], 'unit_cost' => $data['unit_cost'] ?? 0];
            if ($data['kind'] === 'part') {
                $product = Product::where('tenant_id', $order->tenant_id)->where('status', 'active')->lockForUpdate()->findOrFail($data['product_id']);
                if ($product->available_stock < $data['quantity']) {
                    throw ValidationException::withMessages(['quantity' => 'Not enough available stock.']);
                }
                $product->decrement('inventory', $data['quantity']);
                $cost = ['product_id' => $product->id, 'description' => $product->name, 'quantity' => $data['quantity'], 'unit_cost' => $product->purchase_price];
                StockMovement::create(['tenant_id' => $order->tenant_id, 'product_id' => $product->id, 'type' => 'service', 'quantity' => -$data['quantity'], 'stock_after' => $product->inventory, 'reference_type' => ServiceOrder::class, 'reference_id' => $order->id, 'user_id' => $request->user()->id, 'notes' => 'Parts consumed by service #'.$order->id]);
                StockNotifier::sync($product);
            }
            $order->costs()->create($cost);
        });
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Service cost recorded.', 'html' => $this->show($request, $service)->render()]);
        }
        return back()->with('status', 'Service cost recorded.');
    }

    public function payment(Request $request, int $service)
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'], 'method' => ['required', 'in:cash,card,upi,bank_transfer']], [
            'amount.required' => 'Please enter a payment amount.',
            'method.required' => 'Please select a payment method.',
        ]);
        DB::transaction(function () use ($request, $service, $data) {
            $order = $this->order($request, $service, true);
            if ((int) round($data['amount'] * 100) > (int) round(($order->charge - $order->paid_amount) * 100)) {
                throw ValidationException::withMessages(['amount' => 'Payment exceeds the outstanding amount.']);
            }
            DB::table('payments')->insert(['tenant_id' => $order->tenant_id, 'payable_type' => ServiceOrder::class, 'payable_id' => $order->id, 'amount' => $data['amount'], 'method' => $data['method'], 'status' => 'paid', 'created_at' => now(), 'updated_at' => now()]);
            $order->increment('paid_amount', $data['amount']);
        });
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Payment received.', 'html' => $this->show($request, $service)->render()]);
        }
        return back()->with('status', 'Payment received.');
    }

    private function order(Request $request, int $id, bool $lock = false): ServiceOrder
    {
        $query = ServiceOrder::where('tenant_id', $request->user()->tenant_id);
        return ($lock ? $query->lockForUpdate() : $query)->findOrFail($id);
    }

    private function details(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'technician_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'service_type' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'charge' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
        ], [
            'customer_id.required' => 'Please select a customer.',
            'customer_id.exists' => 'Please select a valid customer.',
            'service_type.required' => 'Please enter a service type.',
            'charge.required' => 'Please enter the total customer charge.',
            'charge.numeric' => 'The total customer charge must be a number.',
            'charge.min' => 'The total customer charge cannot be negative.',
            'charge.max' => 'The total customer charge cannot exceed 99,999,999.99.',
            'charge.decimal' => 'The total customer charge must have at most two decimal places.',
            'due_date.date_format' => 'Please enter a valid due date.',
        ]);
    }
}
