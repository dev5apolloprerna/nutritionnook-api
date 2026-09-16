<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Orders Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .info { margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f5f5f5; }
        .right { text-align: right; }
        .total { font-weight: bold; }
    </style>
</head>
<body>

<div class="header">
    <h2>Orders Report</h2>
    <p>Generated On: {{ now()->format('d M Y') }}</p>
</div>

<div class="info">
    <p><strong>User:</strong> {{ $user->name }} (ID: {{ $user->id }})</p>
    <p><strong>Filters:</strong>
        From: {{ $filters['from_date'] ?? 'N/A' }},
        To: {{ $filters['to_date'] ?? 'N/A' }},
        Status: {{ $filters['status'] ?? 'All' }},
        Search: {{ $filters['search'] ?? 'N/A' }}
    </p>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Order ID</th>
            <th>Status</th>
            <th>Items</th>
            <th class="right">Amount (₹)</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @php $grandTotal = 0; @endphp

        @foreach($orders as $index => $order)
            @php $grandTotal += $order->amount; @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $order->id }}</td>
                <td>{{ ucfirst($order->status) }}</td>
                <td>
                    @foreach($order->items as $item)
                        {{ $item['name'] ?? '' }} ({{ $item['quantity'] ?? 1 }})<br>
                    @endforeach
                </td>
                <td class="right">{{ number_format($order->amount, 2) }}</td>
                <td>{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y') }}</td>
            </tr>
        @endforeach
    </tbody>

    <tfoot>
        <tr>
            <td colspan="4" class="right total">Grand Total</td>
            <td class="right total">₹{{ number_format($grandTotal, 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

</body>
</html>
