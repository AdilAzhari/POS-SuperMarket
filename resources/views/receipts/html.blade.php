<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $receipt_number }}</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            line-height: 1.3;
        }
        .receipt {
            background: white;
            width: 280px;
            margin: 0 auto;
            padding: 16px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            font-size: 12px;
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #666;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .store-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .store-info {
            font-size: 10px;
            color: #374151;
            line-height: 1.1;
        }
        .receipt-info {
            margin: 8px 0;
            font-size: 10px;
            text-align: center;
        }
        .receipt-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 4px;
        }
        .items {
            border-bottom: 1px dashed #666;
            padding: 8px 0;
            margin: 8px 0;
        }
        .item {
            margin-bottom: 4px;
            font-size: 10px;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1px;
        }
        .item-name {
            flex: 1;
            padding-right: 8px;
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .item-total {
            text-align: right;
            font-weight: 500;
        }
        .item-details {
            display: flex;
            justify-content: space-between;
            margin-left: 8px;
            color: #4b5563;
            font-size: 9px;
        }
        .item-discount {
            display: flex;
            justify-content: space-between;
            margin-left: 8px;
            color: #dc2626;
            font-size: 9px;
        }
        .totals {
            margin: 8px 0;
            font-size: 10px;
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        .grand-total {
            border-top: 3px double #4b5563;
            border-bottom: 1px dashed #666;
            padding: 4px 0;
            margin: 4px 0 8px 0;
            font-weight: bold;
            font-size: 14px;
        }
        .payment-info {
            margin: 8px 0;
            font-size: 10px;
            border-bottom: 1px dashed #666;
            padding-bottom: 8px;
        }
        .payment-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        .footer {
            text-align: center;
            margin-top: 8px;
            font-size: 10px;
        }
        .thank-you {
            font-weight: bold;
            margin-bottom: 4px;
        }
        .footer-info {
            color: #6b7280;
            font-size: 9px;
            margin: 2px 0;
        }
        .loyalty-info {
            background-color: #f0f8ff;
            border: 1px solid #d0e8ff;
            padding: 8px;
            margin: 10px 0;
            border-radius: 3px;
            font-size: 11px;
        }
        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            .receipt {
                box-shadow: none;
                border: none;
                width: 58mm;
                font-size: 10px;
                margin: 0;
                padding: 8px;
            }
            .store-name { font-size: 12px; }
            .receipt-title { font-size: 11px; }
            .grand-total { font-size: 11px; }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <div class="store-name">{{ $store->name }}</div>
            @if($store->address)
                <div class="store-info">{{ $store->address }}</div>
            @endif
            @if($store->phone)
                <div class="store-info">Tel: {{ $store->phone }}</div>
            @endif
            @if($store->email)
                <div class="store-info">{{ $store->email }}</div>
            @endif
        </div>

        <!-- Receipt Info -->
        <div class="receipt-info">
            <div class="receipt-title">SALES RECEIPT</div>
            <div>Receipt #: {{ $receipt_number ?? 'R-000000' }}</div>
            <div>{{ $sale->created_at ? $sale->created_at->format('m/d/Y, H:i A') : now()->format('m/d/Y, H:i A') }}</div>
            <div>Cashier: {{ $sale->cashier?->name ?? 'Staff' }}</div>
            @if($sale->customer)
                <div style="font-weight: 500;">Customer: {{ $sale->customer->name }}</div>
                @if($sale->customer->phone)
                    <div style="font-size: 9px;">Phone: {{ $sale->customer->phone }}</div>
                @endif
            @endif
        </div>
        <div style="border-bottom: 1px dashed #666; margin: 8px 0;"></div>

        <!-- Items -->
        <div class="items">
            @if($sale->items && $sale->items->count() > 0)
                @foreach($sale->items as $item)
                    <div class="item">
                        <div class="item-row">
                            <div class="item-name">{{ $item->product_name ?? 'Unknown Item' }}</div>
                            <div class="item-total">RM {{ number_format($item->line_total ?? ($item->price * $item->quantity), 2) }}</div>
                        </div>
                        <div class="item-details">
                            <div>{{ $item->quantity ?? 1 }} x RM {{ number_format($item->price ?? 0, 2) }}</div>
                            <div style="font-size: 8px;">SKU: {{ $item->sku ?? $item->product?->sku ?? 'N/A' }}</div>
                        </div>
                        @if(($item->discount ?? 0) > 0)
                            <div class="item-discount">
                                <div>Item Discount</div>
                                <div>-RM {{ number_format($item->discount, 2) }}</div>
                            </div>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="item">
                    <div class="item-row">
                        <div class="item-name">No items found</div>
                        <div class="item-total">RM 0.00</div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Totals -->
        <div class="totals">
            <div class="total-line">
                <span>Subtotal:</span>
                <span>RM {{ number_format($sale->subtotal ?? 0, 2) }}</span>
            </div>
            @if(($sale->discount ?? 0) > 0)
                <div class="total-line" style="color: #dc2626;">
                    <span>Discount:</span>
                    <span>-RM {{ number_format($sale->discount, 2) }}</span>
                </div>
            @endif
            @if(($sale->tax ?? 0) > 0)
                <div class="total-line">
                    <span>Tax ({{ $sale->subtotal > 0 ? number_format(($sale->tax / $sale->subtotal) * 100, 1) : '0' }}%):</span>
                    <span>RM {{ number_format($sale->tax, 2) }}</span>
                </div>
            @endif
            <div class="total-line grand-total">
                <span>TOTAL:</span>
                <span>RM {{ number_format($sale->total ?? 0, 2) }}</span>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="payment-info">
            <div class="payment-line">
                <span>Payment:</span>
                <span style="font-weight: 500;">
                    {{ strtoupper(ucfirst(str_replace('_', ' ', 
                        is_string($sale->payment_method ?? 'CASH') ? 
                        ($sale->payment_method ?? 'CASH') : 
                        ($sale->payment_method->value ?? 'CASH')
                    ))) }}
                </span>
            </div>
            @php
                $paymentDetails = $sale->payment_details ?? [];
                $cashReceived = $paymentDetails['cash_received'] ?? $sale->total ?? 0;
                $changeAmount = $paymentDetails['change_amount'] ?? 0;
            @endphp
            @if(($sale->payment_method === 'cash' || !$sale->payment_method) && $cashReceived > 0)
                <div class="payment-line">
                    <span>Amount Tendered:</span>
                    <span>RM {{ number_format($cashReceived, 2) }}</span>
                </div>
                @if($changeAmount > 0)
                    <div class="payment-line" style="font-weight: 500;">
                        <span>Change:</span>
                        <span>RM {{ number_format($changeAmount, 2) }}</span>
                    </div>
                @endif
            @endif
        </div>

        <!-- Loyalty Info -->
        @if($sale->customer && $sale->customer->loyalty_points > 0)
            <div class="loyalty-info">
                <div><strong>Loyalty Points Balance:</strong> {{ number_format($sale->customer->loyalty_points) }}</div>
                @if(isset($points_earned) && $points_earned > 0)
                    <div style="color: #28a745;"><strong>Points Earned:</strong> +{{ $points_earned }}</div>
                @endif
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            @if(isset($settings['header']) && $settings['header'])
                @foreach(explode('\n', $settings['header']) as $index => $line)
                    @if($index === 0)
                        <div class="thank-you">{{ strtoupper($line) }}</div>
                    @else
                        <div style="color: #4b5563; margin-bottom: 8px;">{{ $line }}</div>
                    @endif
                @endforeach
            @else
                <div class="thank-you">THANK YOU!</div>
                <div style="color: #4b5563; margin-bottom: 8px;">Please come again</div>
            @endif
            
            <div class="footer-info">
                @if($store->email)
                    <div>{{ $store->email }}</div>
                @endif
                @if(isset($settings['footer']) && $settings['footer'])
                    @foreach(explode('\n', $settings['footer']) as $line)
                        <div>{{ $line }}</div>
                    @endforeach
                @else
                    <div>Exchange within 7 days with receipt</div>
                    <div>No refund on sale items</div>
                @endif
            </div>

            <!-- Simple Barcode -->
            <div style="margin: 8px 0; font-family: monospace;">
                <div style="font-size: 9px; margin-bottom: 4px;">{{ $receipt_number }}</div>
                <div style="display: flex; justify-content: center; gap: 1px;">
                    @for($i = 0; $i < 24; $i++)
                        <div style="background: black; width: {{ $i % 4 === 0 || $i % 7 === 0 ? '2px' : '1px' }}; height: 24px;"></div>
                    @endfor
                </div>
            </div>
            
            <div class="footer-info">
                {{ $sale->created_at->format('m/d/y H:i') }} | {{ $sale->cashier->name }}
            </div>
        </div>
    </div>

    <script>
        // Auto-print on load if requested
        if (window.location.search.includes('print=true')) {
            window.print();
        }
    </script>
</body>
</html>