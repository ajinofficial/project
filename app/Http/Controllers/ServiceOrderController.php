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
            $request->session()->flash('status', __('orders.service_order_created_f3d02a'));
            return response()->json(['message' => __('orders.service_order_created_f3d02a'), 'redirect' => route('services.show', $order)], 201);
        }
        return redirect()->route('services.show', $order)->with('status', __('orders.service_order_created_f3d02a'));
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
        return back()->with('status', __('orders.service_order_updated_b40e2b'));
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
            'description.required_if' => __('validation.please_enter_an_expense_description_c34cdb'),
            'quantity.required' => __('validation.please_enter_a_quantity_05a2e6'),
            'unit_cost.required_if' => __('validation.please_enter_the_unit_cost_a52196'),
        ]);
        DB::transaction(function () use ($request, $service, $data) {
            $order = $this->order($request, $service, true);
            if ($order->status === 'completed') {
                throw ValidationException::withMessages(['kind' => __('orders.reopen_this_order_before_adding_costs_97abd6')]);
            }
            $cost = ['description' => $data['description'] ?? '', 'quantity' => $data['quantity'], 'unit_cost' => $data['unit_cost'] ?? 0];
            if ($data['kind'] === 'part') {
                $product = Product::where('tenant_id', $order->tenant_id)->where('status', 'active')->lockForUpdate()->findOrFail($data['product_id']);
                if ($product->available_stock < $data['quantity']) {
                    throw ValidationException::withMessages(['quantity' => __('validation.not_enough_available_stock_d9b1db')]);
                }
                $product->decrement('inventory', $data['quantity']);
                $cost = ['product_id' => $product->id, 'description' => $product->name, 'quantity' => $data['quantity'], 'unit_cost' => $product->purchase_price];
                StockMovement::create(['tenant_id' => $order->tenant_id, 'product_id' => $product->id, 'type' => 'service', 'quantity' => -$data['quantity'], 'stock_after' => $product->inventory, 'reference_type' => ServiceOrder::class, 'reference_id' => $order->id, 'user_id' => $request->user()->id, 'notes' => 'Parts consumed by service #'.$order->id]);
                StockNotifier::sync($product);
            }
            $order->costs()->create($cost);
        });
        if ($request->expectsJson()) {
            return response()->json(['message' => __('orders.service_cost_recorded_8eeea7'), 'html' => $this->show($request, $service)->render()]);
        }
        return back()->with('status', __('orders.service_cost_recorded_8eeea7'));
    }

    public function payment(Request $request, int $service)
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'], 'method' => ['required', 'in:cash,card,upi,bank_transfer']], [
            'amount.required' => __('validation.please_enter_a_payment_amount_98c6c3'),
            'method.required' => __('validation.please_select_a_payment_method_0bef3f'),
        ]);
        DB::transaction(function () use ($request, $service, $data) {
            $order = $this->order($request, $service, true);
            if ((int) round($data['amount'] * 100) > (int) round(($order->charge - $order->paid_amount) * 100)) {
                throw ValidationException::withMessages(['amount' => __('validation.payment_exceeds_the_outstanding_amount_ec8bd3')]);
            }
            DB::table('payments')->insert(['tenant_id' => $order->tenant_id, 'payable_type' => ServiceOrder::class, 'payable_id' => $order->id, 'amount' => $data['amount'], 'method' => $data['method'], 'status' => 'paid', 'created_at' => now(), 'updated_at' => now()]);
            $order->increment('paid_amount', $data['amount']);
        });
        if ($request->expectsJson()) {
            return response()->json(['message' => __('orders.payment_received_13884c'), 'html' => $this->show($request, $service)->render()]);
        }
        return back()->with('status', __('orders.payment_received_13884c'));
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
            'customer_id.required' => __('validation.please_select_a_customer_6c33a1'),
            'customer_id.exists' => __('validation.please_select_a_valid_customer_f6842d'),
            'service_type.required' => __('validation.please_enter_a_service_type_f46a57'),
            'charge.required' => __('validation.please_enter_the_total_customer_charge_1d795e'),
            'charge.numeric' => __('validation.the_total_customer_charge_must_be_a_number_c3e646'),
            'charge.min' => __('validation.the_total_customer_charge_cannot_be_negative_ec125f'),
            'charge.max' => __('validation.the_total_customer_charge_cannot_exceed_99_999_999_99_94554d'),
            'charge.decimal' => __('orders.the_total_customer_charge_must_have_at_most_two_decimal_places_e0c2b8'),
            'due_date.date_format' => __('validation.please_enter_a_valid_due_date_e40ab5'),
        ]);
    }
}
