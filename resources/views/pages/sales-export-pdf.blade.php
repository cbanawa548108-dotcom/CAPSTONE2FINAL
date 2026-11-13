<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales Transactions — FreshTrack</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 32px;
            color: #1f2937;
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .report-header {
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #7c3aed;
            text-align: center;
        }

        .logo {
            display: block;
            width: 170px;
            height: auto;
            margin: 0 auto 12px;
        }

        h1 {
            margin: 0 0 6px;
            color: #5b21b6;
            font-size: 22px;
        }

        .meta {
            color: #6b7280;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            display: table-header-group;
        }

        th,
        td {
            padding: 8px;
            border: 1px solid #d1d5db;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #7c3aed;
            color: #fff;
        }

        tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .product-line + .product-line {
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px solid #e5e7eb;
        }

        .empty {
            padding: 24px;
            color: #6b7280;
            text-align: center;
        }

        .footer {
            margin-top: 20px;
            color: #6b7280;
            font-size: 10px;
            text-align: center;
        }

        @media print {
            body {
                margin: 12mm;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .report-header,
            tr {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <header class="report-header">
        <img class="logo" src="{{ asset('images/freshtrack-logo.svg') }}" alt="FreshTrack logo">
        <h1>Sales Transactions</h1>
        <div class="meta">
            {{ $selectedDate->format('F j, Y') }}
            &middot; {{ $sales->count() }} {{ \Illuminate\Support\Str::plural('transaction', $sales->count()) }}
            &middot; Printed {{ now($timezone)->format('F j, Y g:i A') }}
        </div>
    </header>

    <table>
        <thead>
            <tr>
                <th>Transaction ID</th>
                <th>Fruit</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
                <th>Cashier</th>
                <th>Date &amp; Time</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
                <tr>
                    <td>{{ $sale->transaction_code }}</td>
                    <td>
                        @forelse($sale->salesItems as $saleItem)
                            @php $product = $saleItem->inventoryItem ?? $saleItem->inventoryBatch?->inventoryItem; @endphp
                            <div class="product-line">{{ $product?->name ?? 'N/A' }}</div>
                        @empty
                            N/A
                        @endforelse
                    </td>
                    <td>
                        @forelse($sale->salesItems as $saleItem)
                            @php $product = $saleItem->inventoryItem ?? $saleItem->inventoryBatch?->inventoryItem; @endphp
                            <div class="product-line">{{ number_format((float) $saleItem->quantity, 2) }} {{ $product?->unit ?? 'kg' }}</div>
                        @empty
                            —
                        @endforelse
                    </td>
                    <td>
                        @forelse($sale->salesItems as $saleItem)
                            <div class="product-line">₱{{ number_format((float) $saleItem->unit_price, 2) }}</div>
                        @empty
                            —
                        @endforelse
                    </td>
                    <td>₱{{ number_format((float) $sale->total_amount, 2) }}</td>
                    <td>{{ $sale->user?->name ?? 'System' }}</td>
                    <td>{{ $sale->created_at->copy()->timezone($timezone)->format('M j, Y g:i A') }}</td>
                    <td>{{ ucfirst($sale->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="empty" colspan="8">No sales transactions for this date.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <footer class="footer">FreshTrack Inventory Management System</footer>

    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
