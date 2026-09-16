<!DOCTYPE html>
<html>
<head>
    <title>Platform Health Report</title>
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
    <p><strong>Platform Health Report</strong></p>
    <p style="font-size:11px;">{{ $fromDate->format('d M Y') }} - {{ $toDate->format('d M Y') }}</p>
</div>

<div class="section-title">Orders Trend</div>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th class="right">Orders</th>
            <th class="right">Revenue</th>
        </tr>
    </thead>
    <tbody>
        @forelse($ordersTrend as $row)
        <tr>
            <td>{{ $row->date }}</td>
            <td class="right">{{ $row->order_count }}</td>
            <td class="right">₹{{ number_format($row->revenue, 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="3">No data for this period.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="section-title">Peak Ordering Hours</div>
<table>
    <thead>
        <tr>
            <th>Hour</th>
            <th class="right">Orders</th>
            <th class="right">Revenue</th>
        </tr>
    </thead>
    <tbody>
        @forelse($peakHours as $row)
        <tr>
            <td>{{ str_pad($row->hour, 2, '0', STR_PAD_LEFT) }}:00</td>
            <td class="right">{{ $row->order_count }}</td>
            <td class="right">₹{{ number_format($row->revenue, 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="3">No data for this period.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="section-title">Coupon Usage</div>
<table>
    <thead>
        <tr>
            <th>Code</th>
            <th>Type</th>
            <th class="right">Value</th>
            <th class="right">Uses</th>
            <th class="right">Total Discount</th>
            <th class="right">Unique Users</th>
        </tr>
    </thead>
    <tbody>
        @forelse($couponUsage as $row)
        <tr>
            <td>{{ $row->code }}</td>
            <td>{{ $row->type }}</td>
            <td class="right">{{ $row->value }}</td>
            <td class="right">{{ $row->usage_count }}</td>
            <td class="right">₹{{ number_format($row->total_discount, 2) }}</td>
            <td class="right">{{ $row->unique_users }}</td>
        </tr>
        @empty
        <tr><td colspan="6">No data for this period.</td></tr>
        @endforelse
    </tbody>
</table>

<p style="margin-top:20px; font-size:10px; color:#888;">Generated {{ now()->format('d M Y, h:i A') }}</p>

</body>
</html>
