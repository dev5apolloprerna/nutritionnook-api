<!DOCTYPE html>
<html>
<head>
    <title>Nutrition Nook - Invoice</title>
    <style>
        body { font-family: DejaVu Sans; font-size: 12px; color:#333; }
        .header { text-align:center; margin-bottom:20px; }
        .header h2 { margin:0; }
        .box { margin-bottom:15px; }
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { border:1px solid #ddd; padding:8px; }
        th { background:#f2f2f2; }
        .right { text-align:right; }
        .bold { font-weight:bold; }
        .no-border td { border:none; }
    </style>
</head>
<body>

<div class="header">
    <h2>Nutrition Nook</h2>
    <p><strong>Invoice</strong></p>
    <p style="margin-top:5px; font-size:11px;">
        GST: 24EWSPB9927B1ZD <br>
        FSSAI: 10725994000779 <br>
    </p>
</div>

<div class="box">
    <table class="no-border">
        <tr>
            <td>
                <strong>Invoice No:</strong> #{{ $order->id }}<br>
                <strong>Date:</strong> {{ $date }}<br>
                <strong>Payment Status:</strong> {{ ucfirst($order->payment_status) }}
            </td>
            <td class="right">
                <strong>Chef:</strong><br>
                {{ $chef->name ?? '-' }}<br>
                {{ $chef->email ?? '-' }}<br>
                {{ $chef->phone_number ?? '-' }}<br>
                FSSAI:{{ $chef->fssai_license_number }}
            </td>
        </tr>
    </table>
</div>

<div class="box">
    <strong>Billed To:</strong><br>
    {{ $user->name }}<br>
    {{ $user->email }}<br>
    {{ $user->phone_number ?? '' }}
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Item</th>
            <th class="right">Price</th>
            <th class="right">Qty</th>
            <th class="right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $key => $item)
        <tr>
            <td>{{ $key + 1 }}</td>
            <td>{{ $item['name'] }}</td>
            <td class="right">₹{{ number_format($item['price'],2) }}</td>
            <td class="right">{{ $item['quantity'] }}</td>
            <td class="right">₹{{ number_format($item['total'],2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<table>
    <tr>
        <td class="right bold">Subtotal</td>
        <td class="right">₹{{ number_format($subtotal,2) }}</td>
    </tr>
    @if(isset($platformFee) && $platformFee > 0)
    <tr>
        <td class="right bold">Platform Fee</td>
        <td class="right">₹{{ number_format($platformFee, 2) }}</td>
    </tr>
    @endif
    <tr>
        <td class="right bold">GST ({{ $gstPercent }}%)</td>
        <td class="right">₹{{ number_format($gstAmount, 2) }}</td>
    </tr>

    @if($coupon && $order->discount_amount > 0)
    <tr>
        <td class="right bold">
            Discount ({{ $coupon->code }})
        </td>
        <td class="right">- ₹{{ number_format($order->discount_amount,2) }}</td>
    </tr>
    @endif

    <tr>
        <td class="right bold">Final Amount</td>
        <td class="right bold">₹{{ number_format($order->amount,2) }}</td>
    </tr>
</table>

<p style="margin-top:20px;">
    Thank you for ordering from <strong>Nutrition Nook</strong> 💚<br>
    This is a system generated invoice.
</p>

</body>
</html>
