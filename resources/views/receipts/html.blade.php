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
        }
        .receipt {
            background: white;
            width: 300px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .store-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .store-info {
            font-size: 12px;
            color: #666;
        }
        .receipt-info {
            margin: 10px 0;
            font-size: 12px;
        }
        .items {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 10px 0;
            margin: 10px 0;
        }
        .item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
        }
        .item-name {
            flex: 1;
        }
        .item-qty-price {
            text-align: center;
            min-width: 80px;
        }
        .item-total {
            text-align: right;
            min-width: 60px;
        }
        .totals {
            margin: 10px 0;
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 12px;
        }
        .grand-total {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 5px 0;
            font-weight: bold;
            font-size: 14px;
        }
        .payment-info {
            margin: 10px 0;
            font-size: 12px;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dashed #000;
            font-size: 11px;
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
                width: 100%;
            }
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
            <div><strong>Receipt:</strong> {{ $receipt_number }}</div>
            <div><strong>Date:</strong> {{ $sale->created_at->format('Y-m-d H:i:s') }}</div>
            <div><strong>Cashier:</strong> {{ $sale->cashier->name }}</div>
            @if($sale->customer)
                <div><strong>Customer:</strong> {{ $sale->customer->name }}</div>
            @endif
        </div>

        <!-- Items -->
        <div class="items">
            @foreach($sale->items as $item)
                <div class="item">
                    <div class="item-name">{{ $item->product_name }}</div>
                    <div class="item-qty-price">{{ $item->quantity }} × ${{ number_format($item->price, 2) }}</div>
                    <div class="item-total">${{ number_format($item->line_total, 2) }}</div>
                </div>
                @if($item->discount > 0)
                    <div class="item" style="color: #d33; font-size: 10px;">
                        <div class="item-name">Item Discount</div>
                        <div class="item-qty-price"></div>
                        <div class="item-total">-${{ number_format($item->discount, 2) }}</div>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- Totals -->
        <div class="totals">
            <div class="total-line">
                <span>Subtotal:</span>
                <span>${{ number_format($sale->subtotal, 2) }}</span>
            </div>
            @if($sale->discount > 0)
                <div class="total-line" style="color: #d33;">
                    <span>Discount:</span>
                    <span>-${{ number_format($sale->discount, 2) }}</span>
                </div>
            @endif
            @if($sale->tax > 0)
                <div class="total-line">
                    <span>Tax:</span>
                    <span>${{ number_format($sale->tax, 2) }}</span>
                </div>
            @endif
            <div class="total-line grand-total">
                <span>TOTAL:</span>
                <span>${{ number_format($sale->total, 2) }}</span>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="payment-info">
            <strong>Payment Method:</strong> {{ ucfirst(str_replace('_', ' ', is_string($sale->payment_method) ? $sale->payment_method : $sale->payment_method->value)) }}
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
            @if($settings['receipt_footer'] ?? null)
                <div>{{ $settings['receipt_footer'] }}</div>
            @endif
            <div style="margin-top: 10px;">
                <strong>Thank you for your purchase!</strong>
            </div>
            @if($settings['website'] ?? null)
                <div style="margin-top: 5px;">{{ $settings['website'] }}</div>
            @endif
            <div style="margin-top: 10px; font-size: 10px; color: #999;">
                Generated: {{ $generated_at->format('Y-m-d H:i:s') }}
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