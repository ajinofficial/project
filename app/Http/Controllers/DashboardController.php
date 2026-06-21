<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesItem;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $tenant = $user->tenant;

        $products = Product::where('tenant_id', $user->tenant_id);

        $stats = [
            'products' => (clone $products)->count(),
            'active_products' => (clone $products)->where('status', 'active')->count(),
            'inventory' => (clone $products)->sum('inventory'),
            'drafts' => (clone $products)->where('status', 'draft')->count(),
            'archived' => (clone $products)->where('status', 'archived')->count(),
            'low_stock' => (clone $products)->whereColumn('inventory', '<=', 'minimum_stock_level')->where('inventory', '>', 0)->count(),
            'out_of_stock' => (clone $products)->where('inventory', 0)->count(),
            'healthy_stock' => (clone $products)->whereColumn('inventory', '>', 'minimum_stock_level')->count(),
            'inventory_value' => (clone $products)->selectRaw('COALESCE(SUM(price * inventory), 0) as value')->value('value'),
            'categories' => (clone $products)->whereNotNull('category')->distinct('category')->count('category'),
            'today_sales' => SalesOrder::where('tenant_id', $user->tenant_id)->whereDate('created_at', today())->sum('total_amount'),
            'monthly_revenue' => SalesOrder::where('tenant_id', $user->tenant_id)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total_amount'),
            'monthly_purchase' => PurchaseOrder::where('tenant_id', $user->tenant_id)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total_amount'),
            'today_orders' => SalesOrder::where('tenant_id', $user->tenant_id)->whereDate('created_at', today())->count(),
            'outstanding' => SalesOrder::where('tenant_id', $user->tenant_id)->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as value')->value('value'),
            'gross_profit' => SalesItem::whereHas('product', fn ($query) => $query->where('tenant_id', $user->tenant_id))
                ->join('products', 'sales_items.product_id', '=', 'products.id')
                ->selectRaw('COALESCE(SUM((sales_items.selling_price - products.purchase_price) * sales_items.quantity), 0) as value')
                ->value('value'),
        ];

        $recentProducts = (clone $products)
            ->latest()
            ->take(5)
            ->get();

        $lowStockProducts = (clone $products)
            ->whereColumn('inventory', '<=', 'minimum_stock_level')
            ->orderBy('inventory')
            ->take(6)
            ->get();

        $categoryBreakdown = (clone $products)
            ->selectRaw("COALESCE(NULLIF(category, ''), 'Uncategorized') as label, COUNT(*) as total, SUM(inventory) as units")
            ->groupBy('label')
            ->orderByDesc('units')
            ->take(5)
            ->get();

        $stockHealthTotal = max(1, $stats['products']);
        $statusBreakdown = [
            [
                'label' => 'Sellable',
                'value' => $stats['active_products'],
                'route' => route('products.index', ['status' => 'active']),
                'class' => 'is-green',
                'share' => round(($stats['active_products'] / $stockHealthTotal) * 100),
            ],
            [
                'label' => 'Receiving',
                'value' => $stats['drafts'],
                'route' => route('products.index', ['status' => 'draft']),
                'class' => 'is-amber',
                'share' => round(($stats['drafts'] / $stockHealthTotal) * 100),
            ],
            [
                'label' => 'Discontinued',
                'value' => $stats['archived'],
                'route' => route('products.index', ['status' => 'archived']),
                'class' => 'is-gray',
                'share' => round(($stats['archived'] / $stockHealthTotal) * 100),
            ],
        ];

        $recentMovements = StockMovement::with('product')
            ->where('tenant_id', $user->tenant_id)
            ->latest()
            ->take(6)
            ->get();

        $topProducts = SalesItem::with('product')
            ->whereHas('product', fn ($query) => $query->where('tenant_id', $user->tenant_id))
            ->selectRaw('product_id, SUM(quantity) as sold')
            ->groupBy('product_id')
            ->orderByDesc('sold')
            ->take(5)
            ->get();

        $recentSales = SalesOrder::with('customer')
            ->where('tenant_id', $user->tenant_id)
            ->latest()
            ->take(5)
            ->get();

        $salesByDay = SalesOrder::where('tenant_id', $user->tenant_id)
            ->whereDate('created_at', '>=', now()->subDays(6)->toDateString())
            ->selectRaw('DATE(created_at) as day, COALESCE(SUM(total_amount), 0) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $purchasesByDay = PurchaseOrder::where('tenant_id', $user->tenant_id)
            ->whereDate('created_at', '>=', now()->subDays(6)->toDateString())
            ->selectRaw('DATE(created_at) as day, COALESCE(SUM(total_amount), 0) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $trend = collect(range(6, 0))->map(function (int $daysAgo) use ($salesByDay, $purchasesByDay) {
            $date = now()->subDays($daysAgo);
            $key = $date->toDateString();

            return [
                'label' => $date->format('d M'),
                'sales' => (float) ($salesByDay[$key] ?? 0),
                'purchases' => (float) ($purchasesByDay[$key] ?? 0),
            ];
        });

        $trendMax = max(1, $trend->max(fn ($day) => max($day['sales'], $day['purchases'])));

        return view('dashboard', compact('tenant', 'stats', 'recentProducts', 'lowStockProducts', 'categoryBreakdown', 'statusBreakdown', 'recentMovements', 'topProducts', 'recentSales', 'trend', 'trendMax'));
    }
}
