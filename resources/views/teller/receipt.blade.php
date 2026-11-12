{{-- resources/views/teller/receipt.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Receipt · {{ $sale->reference_no }}</title>

    {{-- dynamic @page rules are injected here by JS --}}
    <style id="page-style">
        @page {
            size: 80mm 200mm;
            margin: 0;
        }
    </style>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            background: #fff;
            color: #000;
        }

        body {
            font-family: "Courier New", Courier, monospace;
            font-size: 12px;
            line-height: 1.35;
            padding: 8mm 6mm;
            max-width: 80mm;
            margin: 0 auto;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: 700;
        }

        .muted {
            color: #444;
        }

        .sm {
            font-size: 10px;
        }

        .xs {
            font-size: 9px;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 6px;
        }

        .row .left {
            flex: 1;
            min-width: 0;
        }

        .row .left .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .item-line {
            margin-bottom: 4px;
        }

        .item-name {
            font-size: 11px;
        }

        .item-meta {
            font-size: 9px;
            color: #444;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            font-weight: 700;
            margin: 4px 0;
        }

        /* On-screen action bar (never printed) */
        .actions {
            margin-top: 20px;
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .actions button,
        .actions a {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 12px;
            padding: 8px 16px;
            border: 1px solid #999;
            background: #f5f5f5;
            cursor: pointer;
            border-radius: 4px;
            text-decoration: none;
            color: #000;
        }

        .actions button:hover,
        .actions a:hover {
            background: #e5e5e5;
        }

        @media print {
            body {
                padding: 4mm 3mm;
            }

            .actions {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    {{-- Wrap the receipt in a container so we can measure its height --}}
    <div id="receipt-body">

        {{-- Salon header --}}
        <div class="center">
            <div class="bold" style="font-size: 14px;">MI BELLA - VIDA</div>
            <div class="xs muted">Beauty Salon and Spa</div>
        </div>

        <div class="divider"></div>

        {{-- Meta --}}
        <div class="xs">
            <div class="row">
                <span>Ref:</span>
                <span class="bold">{{ $sale->reference_no }}</span>
            </div>
            <div class="row">
                <span>Date:</span>
                <span>{{ $businessDate }}</span>
            </div>
            <div class="row">
                <span>Cashier:</span>
                <span>{{ $cashierName }}</span>
            </div>
        </div>

        <div class="divider"></div>

        {{-- Items --}}
        <div>
            @foreach ($sale->items as $item)
                <div class="item-line">
                    <div class="row">
                        <div class="left truncate item-name">{{ $item->item_name }}</div>
                        <div class="bold">₱{{ number_format((float) $item->line_total, 2) }}</div>
                    </div>
                    <div class="item-meta">
                        {{ $item->quantity }} × ₱{{ number_format((float) $item->unit_price, 2) }}
                    </div>
                </div>
            @endforeach
        </div>

        <div class="divider"></div>

        {{-- Totals --}}
        <div class="xs">
            <div class="row">
                <span>Subtotal</span>
                <span>₱{{ number_format((float) $sale->subtotal, 2) }}</span>
            </div>
            @if ((float) $sale->discount > 0)
                <div class="row">
                    <span>Discount</span>
                    <span>-₱{{ number_format((float) $sale->discount, 2) }}</span>
                </div>
            @endif
        </div>

        <div class="total-line">
            <span>TOTAL</span>
            <span>₱{{ number_format((float) $sale->total, 2) }}</span>
        </div>

        <div class="xs">
            <div class="row">
                <span>Paid ({{ ucfirst($sale->payment_method) }})</span>
                <span>₱{{ number_format((float) $sale->amount_paid, 2) }}</span>
            </div>
            <div class="row">
                <span>Change</span>
                <span>₱{{ number_format((float) $sale->change_due, 2) }}</span>
            </div>
        </div>

        <div class="divider"></div>

        <div class="center xs muted">
            Thank you for your visit!
        </div>

    </div>

    {{-- Action bar — hidden when printing --}}
    <div class="actions">
        <button onclick="window.print()">Print again</button>
        <a href="{{ route('teller.index') }}">Back to POS</a>
    </div>

    <script>
        (function () {
            /**
             * Measure the receipt's on-screen height and inject a @page rule
             * that makes the print page exactly that tall. Fixes Chrome/Edge
             * ignoring "size: 80mm auto".
             */
            function fitPageToReceipt() {
                const receipt = document.getElementById("receipt-body");
                if (!receipt) return;

                // Measure the rendered height in pixels
                const rect = receipt.getBoundingClientRect();
                const pxHeight = rect.height;

                // Convert px → mm (96 px = 1 inch = 25.4 mm)
                const pxToMm = (px) => (px * 25.4) / 96;

                // Add print padding (4mm top + 4mm bottom + tiny cushion)
                const basePaddingMm = 8 + 3; // 8mm base + 3mm cushion
                const heightMm = Math.ceil(pxToMm(pxHeight) + basePaddingMm);

                // Inject the @page rule
                const style = document.getElementById("page-style");
                style.textContent = `
                    @page {
                        size: 80mm ${heightMm}mm;
                        margin: 0;
                    }
                `;

                // Also reflect on the on-screen body so preview matches paper
                document.body.style.minHeight = `${heightMm}mm`;
            }

            // Fire print after the layout settles
            function autoPrint() {
                fitPageToReceipt();

                // Let the browser reflow with the new @page rule
                setTimeout(() => window.print(), 150);
            }

            window.addEventListener("load", () => {
                // Small delay so fonts and any late layout finish
                setTimeout(autoPrint, 150);
            });

            // Close tab after printing (only when opened by us)
            window.addEventListener("afterprint", () => {
                if (window.opener) {
                    window.close();
                }
            });

            // Re-fit if the user resizes or re-clicks Print
            document
                .querySelector(".actions button")
                ?.addEventListener("click", (e) => {
                    e.preventDefault();
                    fitPageToReceipt();
                    setTimeout(() => window.print(), 100);
                });
        })();
    </script>
</body>

</html>