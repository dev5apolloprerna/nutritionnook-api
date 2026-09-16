<!DOCTYPE html>
<html>
<head>
    <title>Order & Revenue Report</title>
    <style>
        body { font-family: DejaVu Sans; font-size: 12px; color:#333; }
        .header { text-align:center; margin-bottom:20px; }
        .header h2 { margin:0; }
        table { width:100%; border-collapse:collapse; margin-top:15px; }
        th, td { border:1px solid #ddd; padding:6px 8px; }
        th { background:#f2f2f2; text-align:left; }
        .right { text-align:right; }
        .section-title { font-weight:bold; font-size:13px; margin-top:20px; }
    </style>
</head>
<body>

<div class="header">
    <h2>Nutrition Nook</h2>
    <p><strong>Order &amp; Revenue Report</strong></p>
    <p style="font-size:11px;">{{ $fromDate->format('d M Y') }} - {{ $toDate->format('d M Y') }}</p>
</div>

<div class="section-title">Order Trends</div>
<table>
    <thead>
        <tr>
            <th>Period</th>
            <th class="right">Total Orders</th>
            <th class="right">Total Revenue</th>
            <th class="right">Cancelled Orders</th>
        </tr>
    </thead>
    <tbody>
        @forelse($orderTrends as $row)
        <tr>
            <td>{{ $row->period }}</td>
            <td class="right">{{ $row->total_orders }}</td>
            <td class="right">₹{{ number_format($row->total_revenue, 2) }}</td>
            <td class="right">{{ $row->cancelled_orders }}</td>
        </tr>
        @empty
        <tr><td colspan="4">No data for this period.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="section-title">Order Status Breakdown</div>
<table>
    <thead>
        <tr>
            <th>Status</th>
            <th class="right">Count</th>
            <th class="right">Revenue</th>
        </tr>
    </thead>
    <tbody>
        @forelse($orderStatusData as $row)
        <tr>
            <td>{{ ucfirst($row->status) }}</td>
            <td class="right">{{ $row->count }}</td>
            <td class="right">₹{{ number_format($row->revenue, 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="3">No data for this period.</td></tr>
        @endforelse
    </tbody>
</table>

<p style="margin-top:20px; font-size:10px; color:#888;">Generated {{ now()->format('d M Y, h:i A') }}</p>

</body>
</html>
