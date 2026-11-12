{{-- resources/views/teller/report.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Shift Report · {{ $summary['business_date'] }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #111;
            background: #fff;
            padding: 15mm 15mm;
            max-width: 210mm;
            margin: 0 auto;
        }

        /* ---------- Header ---------- */
        .header {
            text-align: center;
            margin-bottom: 12px;
        }

        .header h1 {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .header .sub {
            font-size: 11px;
            color: #555;
        }

        .meta {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 11px;
            margin-bottom: 14px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 8px 0;
        }

        .meta>div {
            flex: 1;
        }

        .meta .label {
            font-weight: 700;
        }

        /* ---------- Sections ---------- */
        section {
            margin-bottom: 16px;
        }

        h2 {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }

        /* ---------- Tables ---------- */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        th,
        td {
            text-align: left;
            padding: 4px 6px;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #f3f3f3;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }

        td.right,
        th.right {
            text-align: right;
        }

        td.center,
        th.center {
            text-align: center;
        }

        /* ---------- Summary boxes ---------- */
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 12px;
        }

        .stat {
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 8px;
        }

        .stat .label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666;
        }

        .stat .value {
            font-size: 14px;
            font-weight: 700;
            margin-top: 2px;
        }

        .cash-rows {
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 12px;
        }

        .cash-rows .row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
        }

        .cash-rows .row.total {
            border-top: 1px solid #000;
            margin-top: 6px;
            padding-top: 8px;
            font-weight: 700;
            font-size: 13px;
        }

        /* ---------- Signature block ---------- */
        .signatures {
            margin-top: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .signature {
            text-align: center;
        }

        .signature .line {
            border-top: 1px solid #000;
            padding-top: 4px;
            margin-top: 48px;
        }

        .signature .name {
            font-weight: 700;
            font-size: 12px;
        }

        .signature .role {
            font-size: 10px;
            color: #555;
        }

        .footer {
            margin-top: 30px;
            font-size: 10px;
            color: #666;
            text-align: center;
        }

        /* ---------- Print tweaks ---------- */
        @media print {
            body {
                padding: 10mm;
            }

            .no-print {
                display: none !important;
            }

            @page {
                margin: 10mm;
            }
        }

        .no-print {
            text-align: center;
            margin-top: 20px;
        }

        .no-print button,
        .no-print a {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 12px;
            padding: 8px 16px;
            margin: 0 4px;
            border: 1px solid #999;
            background: #f5f5f5;
            cursor: pointer;
            border-radius: 4px;
            text-decoration: none;
            color: #000;
        }
    </style>
</head>

<body>

    {{-- Header --}}
    <div class="header">
        <h1>MI BELLA - VIDA</h1>
        <div class="sub">Beauty Salon and Spa</div>
        <h2 style="border: none; font-size: 13px; margin-top: 8px;">
            Shift Summary Report
        </h2>
    </div>

    {{-- Meta --}}
    <div class="meta">
        <div>
            <div class="label">Cashier</div>
            <div>{{ $cashierName }}</div>
        </div>
        <div>
            <div class="label">Business Date</div>
            <div>{{ \Illuminate\Support\Carbon::parse($summary['business_date'])->format('F d, Y') }}</div>
        </div>
        <div>
            <div class="label">Generated</div>
            <div>{{ $generatedAt }}</div>
        </div>
    </div>

    {{-- Sales summary --}}
    <section>
        <h2>Sales Summary</h2>
        <div class="stats">
            <div class="stat">
                <div class="label">Transactions</div>
                <div class="value">{{ $summary['sales']['count'] }}</div>
            </div>
            <div class="stat">
                <div class="label">Subtotal</div>
                <div class="value">₱{{ number_format($summary['sales']['subtotal'], 2) }}</div>
            </div>
            <div class="stat">
                <div class="label">Discounts</div>
                <div class="value">₱{{ number_format($summary['sales']['discount_total'], 2) }}</div>
            </div>
            <div class="stat">
                <div class="label">Gross Total</div>
                <div class="value">₱{{ number_format($summary['sales']['gross_total'], 2) }}</div>
            </div>
        </div>
    </section>

    {{-- Cash summary --}}
    <section>
        <h2>Cash Report</h2>
        <div class="cash-rows">
            <div class="row">
                <span>Cash Beginning</span>
                <span>₱{{ number_format($summary['cash']['beginning'], 2) }}</span>
            </div>
            <div class="row">
                <span>Cash In</span>
                <span>₱{{ number_format($summary['cash']['cash_in'], 2) }}</span>
            </div>
            <div class="row">
                <span>Cash Out</span>
                <span>₱{{ number_format($summary['cash']['cash_out'], 2) }}</span>
            </div>
            <div class="row total">
                <span>Expected Drawer Balance</span>
                <span>₱{{ number_format($summary['cash']['expected_drawer'], 2) }}</span>
            </div>
        </div>
    </section>

    {{-- Payment methods --}}
    @if(count($summary['payment_breakdown']))
        <section>
            <h2>Payment Methods</h2>
            <table>
                <thead>
                    <tr>
                        <th>Method</th>
                        <th class="center">Count</th>
                        <th class="right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summary['payment_breakdown'] as $p)
                        <tr>
                            <td style="text-transform: capitalize;">{{ $p['payment_method'] }}</td>
                            <td class="center">{{ $p['count'] }}</td>
                            <td class="right">₱{{ number_format($p['total'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    {{-- Items sold --}}
    @if(count($summary['item_breakdown']))
        <section>
            <h2>Items Sold</h2>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="center">Qty</th>
                        <th class="right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summary['item_breakdown'] as $item)
                        <tr>
                            <td>
                                {{ $item['item_name'] }}
                                <span style="font-size: 9px; color: #888;">
                                    ({{ ucfirst($item['item_type']) }}@if($item['item_sku']) · {{ $item['item_sku'] }}@endif)
                                </span>
                            </td>
                            <td class="center">{{ $item['quantity_sold'] }}</td>
                            <td class="right">₱{{ number_format($item['total_sales'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    {{-- Transaction list --}}
    @if(count($summary['sales']['items']))
        <section>
            <h2>Transactions</h2>
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Reference</th>
                        <th>Method</th>
                        <th class="center">Items</th>
                        <th class="right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summary['sales']['items'] as $t)
                        <tr>
                            <td>{{ $t['created_at'] }}</td>
                            <td>{{ $t['reference_no'] }}</td>
                            <td style="text-transform: capitalize;">{{ $t['payment_method'] }}</td>
                            <td class="center">{{ $t['items_count'] }}</td>
                            <td class="right">₱{{ number_format($t['total'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    {{-- Signature block --}}
    <div class="signatures">
        <div class="signature">
            <div class="line"></div>
            <div class="name">{{ $cashierName }}</div>
            <div class="role">Cashier</div>
        </div>
        <div class="signature">
            <div class="line"></div>
            <div class="name">__________________________</div>
            <div class="role">Manager / Supervisor</div>
        </div>
    </div>

    <div class="footer">
        Generated by the Salon Management System · {{ $generatedAt }}
    </div>

    <div class="no-print">
        <button onclick="window.print()">Print</button>
        <a href="{{ route('teller.report.index') }}">Back to Reports</a>
    </div>

    <script>
        window.addEventListener("load", () => {
            setTimeout(() => window.print(), 300);
        });
        window.addEventListener("afterprint", () => {
            if (window.opener) window.close();
        });
    </script>
</body>

</html>