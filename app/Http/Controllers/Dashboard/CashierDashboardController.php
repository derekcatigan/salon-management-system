<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Denomination;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CashierDashboardController extends Controller
{
    public function index()
    {
        $cashierId = Auth::id();

        /* --------------------- Inventory -------------------- */
        $inventoryStats = [
            'total_products' => Product::count(),
            'in_stock' => Product::whereColumn('stock', '>', 'reorder_level')->count(),
            'low_stock' => Product::lowStock()->count(),
            'out_of_stock' => Product::outOfStock()->count(),
        ];

        /* -------------------- Services --------------------- */
        $serviceStats = [
            'total' => Service::count(),
            'active' => Service::active()->count(),
            'avg_price' => (float) Service::query()->avg('price'),
        ];

        /* --------------- Teller (this cashier) -------------- */
        $tellerStats = [
            'today_sales_count' => Sale::completed()->today()
                ->where('cashier_id', $cashierId)->count(),
            'today_revenue' => (float) Sale::completed()->today()
                ->where('cashier_id', $cashierId)->sum('total'),
            'cash_in' => (float) Denomination::whereDate('created_at', today())
                ->where('cashier_id', $cashierId)
                ->where('type', 'cash_in')->sum('amount'),
            'cash_out' => (float) Denomination::whereDate('created_at', today())
                ->where('cashier_id', $cashierId)
                ->where('type', 'cash_out')->sum('amount'),
        ];
        $tellerStats['drawer'] = $tellerStats['cash_in'] - $tellerStats['cash_out'];

        /* -------- Sales trend (this cashier, 7 days) -------- */
        $salesTrend = collect(range(6, 0))->map(function ($daysAgo) use ($cashierId) {
            $date = now()->subDays($daysAgo)->startOfDay();

            return [
                'label' => $date->format('M d'),
                'value' => (float) Sale::completed()
                    ->where('cashier_id', $cashierId)
                    ->whereDate('created_at', $date)
                    ->sum('total'),
            ];
        })->values();

        /* -------- Top services sold today (pie) ------------- */
        $topServicesToday = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.cashier_id', $cashierId)
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', today())
            ->where('sale_items.item_type', 'service')
            ->select(
                'sale_items.item_name',
                DB::raw('SUM(sale_items.quantity) as qty')
            )
            ->groupBy('sale_items.item_name')
            ->orderByDesc('qty')
            ->limit(6)
            ->get()
            ->map(fn ($s) => [
                'label' => $s->item_name,
                'value' => (int) $s->qty,
            ]);

        /* -------- Payment method split today (donut) -------- */
        $paymentSplit = Sale::completed()
            ->today()
            ->where('cashier_id', $cashierId)
            ->select('payment_method', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($s) => [
                'label' => ucfirst($s->payment_method),
                'value' => (int) $s->count,
            ]);

        return Inertia::render('Dashboard/CashierDashboard', [
            'inventoryStats' => $inventoryStats,
            'serviceStats' => $serviceStats,
            'tellerStats' => $tellerStats,
            'salesTrend' => $salesTrend,
            'topServicesToday' => $topServicesToday,
            'paymentSplit' => $paymentSplit,
        ]);
    }
}
