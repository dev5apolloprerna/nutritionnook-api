<!DOCTYPE html>
<html>
<head>
    <title>Customer Insights Report</title>
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
    <p><strong>Customer Insights Report</strong></p>
    <p style="font-size:11px;">{{ $fromDate->format('d M Y') }} - {{ $toDate->format('d M Y') }}</p>
</div>

<div class="section-title">Top Customers</div>
<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Phone</th>
            <th class="right">Orders</th>
            <th class="right">Total Spent</th>
            <th class="right">Avg Order</th>
            <th>Last Order</th>
        </tr>
    </thead>
    <tbody>
        @forelse($topCustomers as $row)
        <tr>
            <td>{{ $row->name }}</td>
            <td>{{ $row->phone_number }}</td>
            <td class="right">{{ $row->order_count }}</td>
            <td class="right">₹{{ number_format($row->total_spent, 2) }}</td>
            <td class="right">₹{{ number_format($row->avg_order_value, 2) }}</td>
            <td>{{ $row->last_order_date }}</td>
        </tr>
        @empty
        <tr><td colspan="6">No data for this period.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="section-title">Customer Ratings Given</div>
<table>
    <thead>
        <tr>
            <th>Name</th>
            <th class="right">Total Ratings</th>
            <th class="right">Avg Rating</th>
            <th class="right">5★</th>
            <th class="right">4★</th>
            <th class="right">3★</th>
            <th class="right">2★</th>
            <th class="right">1★</th>
        </tr>
    </thead>
    <tbody>
        @forelse($customerRatings as $row)
        <tr>
            <td>{{ $row->name }}</td>
            <td class="right">{{ $row->total_ratings_given }}</td>
            <td class="right">{{ $row->avg_rating_given }}</td>
            <td class="right">{{ $row->five_star_ratings }}</td>
            <td class="right">{{ $row->four_star_ratings }}</td>
            <td class="right">{{ $row->three_star_ratings }}</td>
            <td class="right">{{ $row->two_star_ratings }}</td>
            <td class="right">{{ $row->one_star_ratings }}</td>
        </tr>
        @empty
        <tr><td colspan="8">No data for this period.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="section-title">Customer Acquisition</div>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th class="right">New Customers</th>
        </tr>
    </thead>
    <tbody>
        @forelse($acquisitionData as $row)
        <tr>
            <td>{{ $row->date }}</td>
            <td class="right">{{ $row->new_customers }}</td>
        </tr>
        @empty
        <tr><td colspan="2">No data for this period.</td></tr>
        @endforelse
    </tbody>
</table>

<p style="margin-top:20px; font-size:10px; color:#888;">Generated {{ now()->format('d M Y, h:i A') }}</p>

</body>
</html>
