<!DOCTYPE html>
<html>
<head>
    <title>Chef Performance Report</title>
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
    <p><strong>Chef Performance Report</strong></p>
    <p style="font-size:11px;">{{ $fromDate->format('d M Y') }} - {{ $toDate->format('d M Y') }}</p>
</div>

<div class="section-title">Chef Earnings</div>
<table>
    <thead>
        <tr>
            <th>Chef</th>
            <th class="right">Orders</th>
            <th class="right">Gross Earnings</th>
            <th class="right">Commission</th>
            <th class="right">Net Earnings</th>
            <th class="right">Avg Rating</th>
        </tr>
    </thead>
    <tbody>
        @forelse($chefEarnings as $row)
        <tr>
            <td>{{ $row->name }}</td>
            <td class="right">{{ $row->total_orders }}</td>
            <td class="right">₹{{ number_format($row->gross_earnings, 2) }}</td>
            <td class="right">₹{{ number_format($row->commission_amount, 2) }}</td>
            <td class="right">₹{{ number_format($row->net_earnings, 2) }}</td>
            <td class="right">{{ $row->avg_rating ? number_format($row->avg_rating, 1) : '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="6">No data for this period.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="section-title">Acceptance Rate</div>
<table>
    <thead>
        <tr>
            <th>Chef</th>
            <th class="right">Total Orders</th>
            <th class="right">Accepted</th>
            <th class="right">Rejected</th>
            <th class="right">Acceptance Rate</th>
        </tr>
    </thead>
    <tbody>
        @forelse($chefAcceptance as $row)
        <tr>
            <td>{{ $row->name }}</td>
            <td class="right">{{ $row->total_orders }}</td>
            <td class="right">{{ $row->accepted_orders }}</td>
            <td class="right">{{ $row->rejected_orders }}</td>
            <td class="right">{{ $row->acceptance_rate }}%</td>
        </tr>
        @empty
        <tr><td colspan="5">No data for this period.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="section-title">Top Selling Items</div>
<table>
    <thead>
        <tr>
            <th>Item</th>
            <th>Chef</th>
            <th class="right">Orders</th>
            <th class="right">Revenue</th>
            <th class="right">Price</th>
        </tr>
    </thead>
    <tbody>
        @forelse($topItems as $row)
        <tr>
            <td>{{ $row->name }}</td>
            <td>{{ $row->chef_name }}</td>
            <td class="right">{{ $row->order_count }}</td>
            <td class="right">₹{{ number_format($row->total_revenue, 2) }}</td>
            <td class="right">₹{{ number_format($row->price, 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="5">No data for this period.</td></tr>
        @endforelse
    </tbody>
</table>

<p style="margin-top:20px; font-size:10px; color:#888;">Generated {{ now()->format('d M Y, h:i A') }}</p>

</body>
</html>
