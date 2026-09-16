<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $order->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .total { text-align: right; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Invoice</h2>
        <p>Order ID: {{ $order->id }}</p>
        <p>Date: {{ $order->date }}</p>
    </div>

    <p><strong>Customer:</strong> {{ $user->name }} (ID: {{ $user->id }})</p>
    <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
    <p><strong>Payment:</strong> {{ $order->payment['method'] ?? 'N/A' }} - {{ $order->payment['status'] ?? '' }}</p>

    <h3>Order Items</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Price (₹)</th>
                <th>Qty</th>
                <th>Total (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td>{{ $item['price'] }}</td>
                <td>{{ $item['quantity'] }}</td>
                <td>{{ number_format($item['price'] * $item['quantity'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p class="total">Grand Total: ₹{{ number_format($order->amount, 2) }}</p>
</body>
</html>
