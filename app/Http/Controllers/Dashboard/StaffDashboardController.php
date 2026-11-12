<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StaffDashboardController extends Controller
{
    public function index()
    {
        /* --------------------- Inventory -------------------- */
        $inventoryStats = [
            'total_products' => Product::count(),
            'in_stock' => Product::whereColumn('stock', '>', 'reorder_level')->count(),
            'low_stock' => Product::lowStock()->count(),
            'out_of_stock' => Product::outOfStock()->count(),
            'total_value' => (float) Product::query()
                ->selectRaw('COALESCE(SUM(cost * stock), 0) as v')
                ->value('v'),
        ];

        // Products grouped by category (pie chart)
        $productsByCategory = Product::query()
            ->select('category', DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($p) => [
                'label' => $p->category,
                'value' => (int) $p->count,
            ]);

        // Low stock alert list
        $lowStockProducts = Product::lowStock()
            ->orderBy('stock')
            ->limit(8)
            ->get()
            ->map(fn ($p) => [
                'name' => $p->name,
                'sku' => $p->sku,
                'stock' => $p->stock,
                'reorder_level' => $p->reorder_level,
                'unit' => $p->unit,
            ]);

        /* -------------------- Services --------------------- */
        $serviceStats = [
            'total' => Service::count(),
            'active' => Service::active()->count(),
            'inactive' => Service::where('status', 'inactive')->count(),
            'avg_price' => (float) Service::query()->avg('price'),
        ];

        $servicesByCategory = Service::query()
            ->select('category', DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($s) => [
                'label' => $s->category,
                'value' => (int) $s->count,
            ]);

        // Top 5 most expensive services (for a small bar chart)
        $topServices = Service::active()
            ->orderByDesc('price')
            ->limit(5)
            ->get()
            ->map(fn ($s) => [
                'label' => $s->name,
                'value' => (float) $s->price,
            ]);

        /* --------------------- Sales ------------------------ */
        // Staff can see today's overall performance (read-only)
        $salesStats = [
            'today_count' => Sale::completed()->today()->count(),
            'today_revenue' => (float) Sale::completed()->today()->sum('total'),
        ];

        return Inertia::render('Dashboard/StaffDashboard', [
            'inventoryStats' => $inventoryStats,
            'serviceStats' => $serviceStats,
            'salesStats' => $salesStats,
            'productsByCategory' => $productsByCategory,
            'servicesByCategory' => $servicesByCategory,
            'lowStockProducts' => $lowStockProducts,
            'topServices' => $topServices,
        ]);
    }
}
