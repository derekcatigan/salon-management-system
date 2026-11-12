<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Models\Denomination;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TellerReportController extends Controller
{
    public function index(Request $request)
    {
        $cashierId = Auth::id();

        $from = $request->query('from');
        $to = $request->query('to');

        // One row per business date the cashier worked
        $query = Sale::query()
            ->select(
                'business_date',
                DB::raw('COUNT(*) as sale_count'),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('SUM(discount) as total_discount'),
            )
            ->where('cashier_id', $cashierId)
            ->where('status', 'completed')
            ->groupBy('business_date')
            ->orderByDesc('business_date');

        if ($from) {
            $query->whereDate('business_date', '>=', $from);
        }

        if ($to) {
            $query->whereDate('business_date', '<=', $to);
        }

        $dates = $query->get()->map(function ($row) use ($cashierId) {
            $businessDate = Carbon::parse($row->business_date)
                ->format('Y-m-d');

            $beginning = (float) Denomination::query()
                ->where('cashier_id', $cashierId)
                ->where('type', 'cash_beginning')
                ->whereDate('business_date', $businessDate)
                ->sum('amount');

            $cashIn = (float) Denomination::query()
                ->where('cashier_id', $cashierId)
                ->where('type', 'cash_in')
                ->whereDate('business_date', $businessDate)
                ->sum('amount');

            $cashOut = (float) Denomination::query()
                ->where('cashier_id', $cashierId)
                ->where('type', 'cash_out')
                ->whereDate('business_date', $businessDate)
                ->sum('amount');

            $expectedDrawer = $beginning + $cashIn - $cashOut;

            return [
                'business_date' => $businessDate,
                'sale_count' => (int) $row->sale_count,
                'total_revenue' => (float) $row->total_revenue,
                'total_discount' => (float) $row->total_discount,
                'cash_beginning' => $beginning,
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'expected_drawer' => $expectedDrawer,
            ];
        });

        return Inertia::render('Teller/TellerReport', [
            'dates' => $dates,
            'filters' => [
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    public function show(string $businessDate)
    {
        $this->ensureOwnership($businessDate);

        $summary = $this->buildSummary($businessDate);

        return response()->json($summary);
    }

    public function print(string $businessDate)
    {
        $this->ensureOwnership($businessDate);

        $summary = $this->buildSummary($businessDate);

        $cashier = Auth::user();
        $cashierName = trim(
            ($cashier->profile?->firstname ?? '').' '.
            ($cashier->profile?->lastname ?? '')
        ) ?: $cashier->email;

        return view('teller.report', [
            'summary' => $summary,
            'cashierName' => $cashierName,
            'generatedAt' => now()->format('M d, Y h:i A'),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Helpers */
    /* ------------------------------------------------------------------ */

    private function ensureOwnership(string $businessDate): void
    {
        $exists = Sale::query()
            ->where('cashier_id', Auth::id())
            ->where('status', 'completed')
            ->whereDate('business_date', $businessDate)
            ->exists();

        $hasDenomination = Denomination::query()
            ->where('cashier_id', Auth::id())
            ->whereDate('business_date', $businessDate)
            ->exists();

        if (! $exists && ! $hasDenomination) {
            abort(404, 'No record for this business date.');
        }
    }

    private function buildSummary(string $businessDate): array
    {
        $cashierId = Auth::id();

        // Sales
        $sales = Sale::query()
            ->where('cashier_id', $cashierId)
            ->where('status', 'completed')
            ->whereDate('business_date', $businessDate)
            ->with(['items'])
            ->orderBy('created_at')
            ->get();

        // Denominations
        $beginning = Denomination::query()
            ->where('cashier_id', $cashierId)
            ->where('type', 'cash_beginning')
            ->whereDate('business_date', $businessDate)
            ->first();

        $cashInTotal = (float) Denomination::query()
            ->where('cashier_id', $cashierId)
            ->where('type', 'cash_in')
            ->whereDate('business_date', $businessDate)
            ->sum('amount');

        $cashOutTotal = (float) Denomination::query()
            ->where('cashier_id', $cashierId)
            ->where('type', 'cash_out')
            ->whereDate('business_date', $businessDate)
            ->sum('amount');

        $beginningAmount = (float) ($beginning?->amount ?? 0);
        $expectedDrawer = $beginningAmount + $cashInTotal - $cashOutTotal;

        // Item breakdown (how many of each item was sold)
        $itemBreakdown = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.cashier_id', $cashierId)
            ->where('sales.status', 'completed')
            ->whereDate('sales.business_date', $businessDate)
            ->select(
                'sale_items.item_type',
                'sale_items.item_name',
                'sale_items.item_sku',
                DB::raw('SUM(sale_items.quantity) as quantity_sold'),
                DB::raw('SUM(sale_items.line_total) as total_sales'),
            )
            ->groupBy('sale_items.item_type', 'sale_items.item_name', 'sale_items.item_sku')
            ->orderByDesc('total_sales')
            ->get()
            ->map(fn ($i) => [
                'item_type' => $i->item_type,
                'item_name' => $i->item_name,
                'item_sku' => $i->item_sku,
                'quantity_sold' => (int) $i->quantity_sold,
                'total_sales' => (float) $i->total_sales,
            ]);

        // Payment method breakdown
        $paymentBreakdown = Sale::query()
            ->where('cashier_id', $cashierId)
            ->where('status', 'completed')
            ->whereDate('business_date', $businessDate)
            ->select(
                'payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as total'),
            )
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($p) => [
                'payment_method' => $p->payment_method,
                'count' => (int) $p->count,
                'total' => (float) $p->total,
            ]);

        return [
            'business_date' => $businessDate,
            'sales' => [
                'count' => $sales->count(),
                'gross_total' => (float) $sales->sum('total'),
                'subtotal' => (float) $sales->sum('subtotal'),
                'discount_total' => (float) $sales->sum('discount'),
                'items' => $sales->map(fn (Sale $s) => [
                    'reference_no' => $s->reference_no,
                    'created_at' => $s->created_at?->format('H:i'),
                    'total' => (float) $s->total,
                    'payment_method' => $s->payment_method,
                    'items_count' => $s->items->count(),
                ]),
            ],
            'cash' => [
                'beginning' => $beginningAmount,
                'cash_in' => $cashInTotal,
                'cash_out' => $cashOutTotal,
                'expected_drawer' => $expectedDrawer,
            ],
            'item_breakdown' => $itemBreakdown,
            'payment_breakdown' => $paymentBreakdown,
        ];
    }
}
