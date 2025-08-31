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
        .summary-item .value { font-size: 24px; font-weight: bold; color: #2563eb; }
        .summary-item .label { font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .amount { text-align: right; }
        .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>Period: {{ $period }}</p>
        <p>Generated on: {{ $generated_at }}</p>
    </div>

    <div class="summary">
        <div class="summary-item">
            <div class="value">${{ number_format($summary['total_revenue'], 2) }}</div>
            <div class="label">Total Revenue</div>
        </div>
        <div class="summary-item">
            <div class="value">{{ $summary['total_sales'] }}</div>
            <div class="label">Total Sales</div>
        </div>
        <div class="summary-item">
            <div class="value">${{ number_format($summary['avg_transaction'], 2) }}</div>
            <div class="label">Average Transaction</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Sale ID</th>
                <th>Date</th>
                <th>Store</th>
                <th>Cashier</th>
                <th>Customer</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $sale)
            <tr>
                <td>{{ $sale->id }}</td>
                <td>{{ $sale->created_at->format('M d, Y H:i') }}</td>
                <td>{{ $sale->store->name ?? 'N/A' }}</td>
                <td>{{ $sale->cashier->name ?? 'N/A' }}</td>
                <td>{{ $sale->customer->name ?? 'Walk-in' }}</td>
                <td class="amount">${{ number_format($sale->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>This report was generated automatically by the POS system.</p>
    </div>
</body>
</html>