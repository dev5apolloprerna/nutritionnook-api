<!DOCTYPE html>
<html>
<head>
    <title>Menu &amp; Cuisine Report</title>
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
    <p><strong>Menu &amp; Cuisine Insights</strong></p>
    <p style="font-size:11px;">{{ $fromDate->format('d M Y') }} - {{ $toDate->format('d M Y') }}</p>
</div>

<div class="section-title">Top Cuisines</div>
<table>
    <thead>
        <tr>
            <th>Cuisine</th>
            <th class="right">Orders</th>
            <th class="right">Revenue</th>
        </tr>
    </thead>
    <tbody>
        @forelse($topCuisines as $row)
        <tr>
            <td>{{ $row->title }}</td>
            <td class="right">{{ $row->order_count }}</td>
            <td class="right">₹{{ number_format($row->revenue, 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="3">No data for this period.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="section-title">Most Ordered Dishes</div>
<table>
    <thead>
        <tr>
            <th>Dish</th>
            <th>Chef</th>
            <th>Cuisine</th>
            <th class="right">Orders</th>
            <th class="right">Revenue</th>
            <th class="right">Price</th>
        </tr>
    </thead>
    <tbody>
        @forelse($mostOrderedDishes as $row)
        <tr>
            <td>{{ $row->name }}</td>
            <td>{{ $row->chef_name }}</td>
            <td>{{ $row->cuisine }}</td>
            <td class="right">{{ $row->order_count }}</td>
            <td class="right">₹{{ number_format($row->total_revenue, 2) }}</td>
            <td class="right">₹{{ number_format($row->price, 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="6">No data for this period.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="section-title">Out of Stock / Disabled Items</div>
<table>
    <thead>
        <tr>
            <th>Dish</th>
            <th>Chef</th>
            <th class="right">Price</th>
            <th>In Stock</th>
            <th>Active</th>
        </tr>
    </thead>
    <tbody>
        @forelse($outOfStockItems as $row)
        <tr>
            <td>{{ $row->name }}</td>
            <td>{{ $row->chef_name }}</td>
            <td class="right">₹{{ number_format($row->price, 2) }}</td>
            <td>{{ $row->in_stock ? 'Yes' : 'No' }}</td>
            <td>{{ $row->is_active ? 'Yes' : 'No' }}</td>
        </tr>
        @empty
        <tr><td colspan="5">No data.</td></tr>
        @endforelse
    </tbody>
</table>

<p style="margin-top:20px; font-size:10px; color:#888;">Generated {{ now()->format('d M Y, h:i A') }}</p>

</body>
</html>
