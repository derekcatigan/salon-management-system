<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Denomination;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        /* ----------------------- Users ----------------------- */
        $userStats = [
            'total' => User::count(),
            'active' => User::active()->count(),
            'inactive' => User::where('status', 'inactive')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
            'by_role' => [
                'admin' => User::where('role', 'admin')->count(),
                'cashier' => User::where('role', 'cashier')->count(),
                'staff' => User::where('role', 'staff')->count(),
            ],
        ];

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

        // Top products by stock value (for bar chart)
        $topProducts = Product::query()
            ->select('name', 'stock', 'cost')
            ->orderByDesc(DB::raw('stock * cost'))
            ->limit(8)
            ->get()
            ->map(fn ($p) => [
                'name' => $p->name,
                'value' => (float) ($p->stock * $p->cost),
            ]);

        /* -------------------- Services --------------------- */
        $serviceStats = [
            'total' => Service::count(),
            'active' => Service::active()->count(),
            'inactive' => Service::where('status', 'inactive')->count(),
            'avg_price' => (float) Service::query()->avg('price'),
        ];

        // Services grouped by category
        $servicesByCategory = Service::query()
            ->select('category', DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($s) => [
                'label' => $s->category,
                'value' => (int) $s->count,
            ]);

        /* --------------------- Sales ------------------------ */
        $salesStats = [
            'today_count' => Sale::completed()->today()->count(),
            'today_revenue' => (float) Sale::completed()->today()->sum('total'),
            'month_count' => Sale::completed()->thisMonth()->count(),
            'month_revenue' => (float) Sale::completed()->thisMonth()->sum('total'),
        ];

        // Sales revenue for last 7 days (line chart)
        $salesTrend = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo)->startOfDay();

            return [
                'label' => $date->format('M d'),
                'value' => (float) Sale::completed()
                    ->whereDate('created_at', $date)
                    ->sum('total'),
            ];
        })->values();

        /* ------------------- Denominations ------------------ */
        $denominationStats = [
            'today_cash_in' => (float) Denomination::whereDate('created_at', today())
                ->where('type', 'cash_in')->sum('amount'),
            'today_cash_out' => (float) Denomination::whereDate('created_at', today())
                ->where('type', 'cash_out')->sum('amount'),
        ];
        $denominationStats['today_drawer'] =
            $denominationStats['today_cash_in'] - $denominationStats['today_cash_out'];

        return Inertia::render('Dashboard/AdminDashboard', [
            'userStats' => $userStats,
            'inventoryStats' => $inventoryStats,
            'serviceStats' => $serviceStats,
            'salesStats' => $salesStats,
            'denominationStats' => $denominationStats,
            'topProducts' => $topProducts,
            'servicesByCategory' => $servicesByCategory,
            'salesTrend' => $salesTrend,
        ]);
    }
}
