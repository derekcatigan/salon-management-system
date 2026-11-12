<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Models\Denomination;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TellerController extends Controller
{
    public function index()
    {
        $businessDate = now()->toDateString();
        $cashierId = Auth::id();

        $services = Service::active()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(fn (Service $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'category' => $s->category,
                'price' => (float) $s->price,
                'duration_minutes' => $s->duration_minutes,
            ]);

        $products = Product::query()
            ->where('stock', '>', 0)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'category' => $p->category,
                'brand' => $p->brand,
                'price' => (float) $p->price,
                'stock' => $p->stock,
                'unit' => $p->unit,
            ]);

        $todaySales = Sale::query()
            ->where('cashier_id', $cashierId)
            ->where('status', 'completed')
            ->whereDate('business_date', $businessDate)
            ->get();

        $beginning = Denomination::query()
            ->where('cashier_id', $cashierId)
            ->where('type', 'cash_beginning')
            ->whereDate('business_date', $businessDate)
            ->first();

        $cashIn = Denomination::query()
            ->where('cashier_id', $cashierId)
            ->where('type', 'cash_in')
            ->whereDate('business_date', $businessDate)
            ->sum('amount');

        $cashOut = Denomination::query()
            ->where('cashier_id', $cashierId)
            ->where('type', 'cash_out')
            ->whereDate('business_date', $businessDate)
            ->sum('amount');

        $drawerCount = Denomination::query()
            ->where('cashier_id', $cashierId)
            ->where('type', 'drawer_count')
            ->whereDate('business_date', $businessDate)
            ->latest()
            ->first();

        $cashBeginning = (float) ($beginning->amount ?? 0);
        $expectedDrawer = $cashBeginning + $cashIn - $cashOut;

        $countedAmount = $drawerCount ? (float) $drawerCount->amount : null;
        $variance = $countedAmount !== null ? ($countedAmount - $expectedDrawer) : null;

        return Inertia::render('Teller/Teller', [
            'services' => $services,
            'products' => $products,
            'recentSales' => $todaySales->sortByDesc('created_at')->take(10)->values()->map(fn (Sale $s) => [
                'id' => $s->id,
                'reference_no' => $s->reference_no,
                'total' => (float) $s->total,
                'created_at' => $s->created_at?->format('H:i'),
            ]),
            'stats' => [
                'business_date' => $businessDate,
                'today_count' => $todaySales->count(),
                'today_revenue' => (float) $todaySales->sum('total'),
                'cash_beginning' => $cashBeginning,
                'cash_in' => (float) $cashIn,
                'cash_out' => (float) $cashOut,
                'cash_drawer' => $expectedDrawer,
                'expected_drawer' => $expectedDrawer,
                'counted_amount' => $countedAmount,
                'variance' => $variance,
                'has_beginning' => (bool) $beginning,
                'has_drawer_count' => (bool) $drawerCount,
            ],
        ]);
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'in:service,product'],
            'items.*.item_id' => ['required', 'uuid'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],

            'discount' => ['nullable', 'numeric', 'min:0'],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,card,gcash,other'],
            'notes' => ['nullable', 'string', 'max:500'],

            'denomination' => ['nullable', 'array'],
            'denomination.*' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            DB::beginTransaction();

            $businessDate = now()->toDateString();

            $resolved = [];
            $subtotal = 0;

            foreach ($data['items'] as $row) {
                $quantity = (int) $row['quantity'];

                if ($row['item_type'] === 'service') {
                    $service = Service::findOrFail($row['item_id']);
                    $unitPrice = (float) $service->price;

                    $resolved[] = [
                        'item_type' => 'service',
                        'item_id' => $service->id,
                        'item_name' => $service->name,
                        'item_sku' => null,
                        'unit_price' => $unitPrice,
                        'quantity' => $quantity,
                        'line_total' => $unitPrice * $quantity,
                    ];
                } else {
                    $product = Product::lockForUpdate()->findOrFail($row['item_id']);

                    if ($product->stock < $quantity) {
                        throw ValidationException::withMessages([
                            'items' => "Not enough stock for {$product->name}. Only {$product->stock} left.",
                        ]);
                    }

                    $unitPrice = (float) $product->price;
                    $resolved[] = [
                        'item_type' => 'product',
                        'item_id' => $product->id,
                        'item_name' => $product->name,
                        'item_sku' => $product->sku,
                        'unit_price' => $unitPrice,
                        'quantity' => $quantity,
                        'line_total' => $unitPrice * $quantity,
                    ];

                    $product->decrement('stock', $quantity);
                }

                $subtotal += end($resolved)['line_total'];
            }

            $discount = (float) ($data['discount'] ?? 0);
            $total = max(0, $subtotal - $discount);
            $amountPaid = (float) $data['amount_paid'];

            if ($amountPaid < $total) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Amount paid is less than the total.',
                ]);
            }

            $changeDue = $amountPaid - $total;

            $sale = Sale::create([
                'reference_no' => Sale::generateReference($businessDate),
                'cashier_id' => Auth::id(),
                'business_date' => $businessDate,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'amount_paid' => $amountPaid,
                'change_due' => $changeDue,
                'payment_method' => $data['payment_method'],
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($resolved as $item) {
                SaleItem::create(array_merge($item, ['sale_id' => $sale->id]));
            }

            if ($data['payment_method'] === 'cash' && ! empty($data['denomination'])) {
                $breakdown = array_filter($data['denomination'], fn ($q) => (int) $q > 0);
                $breakdownTotal = 0;

                foreach ($breakdown as $denomination => $count) {
                    $breakdownTotal += ((int) $denomination) * ((int) $count);
                }

                if ($breakdownTotal > 0) {
                    Denomination::create([
                        'cashier_id' => Auth::id(),
                        'sale_id' => $sale->id,
                        'type' => 'cash_in',
                        'business_date' => $businessDate,
                        'amount' => $breakdownTotal,
                        'breakdown' => $breakdown,
                    ]);
                }
            }

            DB::commit();

            return back()->with([
                'success' => "Sale {$sale->reference_no} completed.",
                'receipt_sale_id' => $sale->id,
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            throw ValidationException::withMessages([
                'checkout' => 'Checkout failed. Please try again.',
            ]);
        }
    }

    public function receipt(Sale $sale)
    {
        if (Auth::user()->role->value === 'cashier' && $sale->cashier_id !== Auth::id()) {
            abort(403);
        }

        $sale->load(['items', 'cashier.profile']);

        return response()->json([
            'reference_no' => $sale->reference_no,
            'business_date' => $sale->business_date?->format('Y-m-d'),
            'created_at' => $sale->created_at?->toIso8601String(),
            'cashier' => [
                'name' => trim(
                    ($sale->cashier?->profile?->firstname ?? '').' '.
                    ($sale->cashier?->profile?->lastname ?? '')
                ) ?: $sale->cashier?->email,
            ],
            'items' => $sale->items->map(fn (SaleItem $i) => [
                'item_type' => $i->item_type,
                'item_name' => $i->item_name,
                'item_sku' => $i->item_sku,
                'unit_price' => (float) $i->unit_price,
                'quantity' => $i->quantity,
                'line_total' => (float) $i->line_total,
            ]),
            'subtotal' => (float) $sale->subtotal,
            'discount' => (float) $sale->discount,
            'total' => (float) $sale->total,
            'amount_paid' => (float) $sale->amount_paid,
            'change_due' => (float) $sale->change_due,
            'payment_method' => $sale->payment_method,
        ]);
    }

    public function printReceipt(Sale $sale)
    {
        if (Auth::user()->role->value === 'cashier' && $sale->cashier_id !== Auth::id()) {
            abort(403);
        }

        $sale->load(['items', 'cashier.profile']);

        return view('teller.receipt', [
            'sale' => $sale,
            'businessDate' => $sale->business_date?->format('Y-m-d'),
            'cashierName' => trim(
                ($sale->cashier?->profile?->firstname ?? '').' '.
                ($sale->cashier?->profile?->lastname ?? '')
            ) ?: $sale->cashier?->email,
        ]);
    }

    public function denomination(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:cash_beginning,cash_in,cash_out,drawer_count'],
            'breakdown' => ['required', 'array'],
            'breakdown.*' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $breakdown = array_filter($data['breakdown'], fn ($q) => (int) $q > 0);
        $total = 0;

        foreach ($breakdown as $denomination => $count) {
            $total += ((int) $denomination) * ((int) $count);
        }

        if ($total <= 0) {
            return back()->withErrors(['breakdown' => 'Enter at least one denomination.']);
        }

        $businessDate = now()->toDateString();

        // Prevent duplicate cash beginning
        if ($data['type'] === 'cash_beginning') {
            $exists = Denomination::query()
                ->where('cashier_id', Auth::id())
                ->where('type', 'cash_beginning')
                ->whereDate('business_date', $businessDate)
                ->exists();

            if ($exists) {
                return back()->withErrors([
                    'breakdown' => 'Cash beginning is already recorded for today.',
                ]);
            }
        }

        // Prevent duplicate drawer count
        if ($data['type'] === 'drawer_count') {
            $exists = Denomination::query()
                ->where('cashier_id', Auth::id())
                ->where('type', 'drawer_count')
                ->whereDate('business_date', $businessDate)
                ->exists();

            if ($exists) {
                return back()->withErrors([
                    'breakdown' => 'Drawer is already counted for today.',
                ]);
            }
        }

        Denomination::create([
            'cashier_id' => Auth::id(),
            'sale_id' => null,
            'type' => $data['type'],
            'business_date' => $businessDate,
            'amount' => $total,
            'breakdown' => $breakdown,
            'notes' => $data['notes'] ?? null,
        ]);

        $label = match ($data['type']) {
            'cash_beginning' => 'Cash beginning',
            'cash_in' => 'Cash in',
            'cash_out' => 'Cash out',
            'drawer_count' => 'Drawer count',
        };

        return back()->with('success', "{$label} recorded.");
    }
}
