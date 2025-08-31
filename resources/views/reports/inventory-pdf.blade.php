<!DOCTYPE html>
<html>
<head>
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #333; margin-bottom: 5px; }
        .header p { color: #666; margin: 0; }
        .summary { 
            background: #f5f5f5; 
            padding: 15px; 
            border-radius: 5px; 
            margin-bottom: 30px;
            display: flex;
            justify-content: space-around;
        }
        .summary-item { text-align: center; }
        .summary-item .value { font-size: 20px; font-weight: bold; color: #2563eb; }
        .summary-item .label { font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 12px; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .amount { text-align: right; }
        .center { text-align: center; }
        .status-low { color: #f59e0b; font-weight: bold; }
        .status-out { color: #ef4444; font-weight: bold; }
        .status-in { color: #10b981; }
        .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>Generated on: {{ $generated_at }}</p>
    </div>

    <div class="summary">
        <div class="summary-item">
            <div class="value">{{ $summary['total_products'] }}</div>
            <div class="label">Total Products</div>
        </div>
        <div class="summary-item">
            <div class="value">{{ $summary['low_stock_items'] }}</div>
            <div class="label">Low Stock Items</div>
        </div>
        <div class="summary-item">
            <div class="value">{{ $summary['out_of_stock_items'] }}</div>
            <div class="label">Out of Stock</div>
        </div>
        <div class="summary-item">
            <div class="value">${{ number_format($summary['total_value'], 2) }}</div>
            <div class="label">Total Inventory Value</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th>Category</th>
                <th class="center">Stock</th>
                <th class="center">Min. Stock</th>
                <th class="amount">Cost</th>
                <th class="amount">Price</th>
                <th class="center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            @php
                $status = 'In Stock';
                $statusClass = 'status-in';
                if ($product->stock == 0) {
                    $status = 'Out of Stock';
                    $statusClass = 'status-out';
                } elseif ($product->stock <= $product->low_stock_threshold) {
                    $status = 'Low Stock';
                    $statusClass = 'status-low';
                }
            @endphp
            <tr>
                <td>{{ $product->name }}</td>
                <td>{{ $product->sku }}</td>
                <td>{{ $product->category->name ?? 'N/A' }}</td>
                <td class="center">{{ $product->stock }}</td>
                <td class="center">{{ $product->low_stock_threshold }}</td>
                <td class="amount">${{ number_format($product->cost_price ?? 0, 2) }}</td>
                <td class="amount">${{ number_format($product->price, 2) }}</td>
                <td class="center {{ $statusClass }}">{{ $status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>This report was generated automatically by the POS system.</p>
    </div>
</body>
</html>