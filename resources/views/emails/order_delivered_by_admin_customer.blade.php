<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Delivered</title>
</head>
<body style="margin:0;padding:0;background:#f2f2f2;font-family:Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="padding:30px 0;">
<tr>
<td align="center">

<table width="540" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;">

<!-- Header -->
<tr>
<td align="center" style="background:#2e7d32;padding:28px 30px;">
<p style="margin:0 0 6px;font-size:30px;">✅</p>
<h1 style="margin:0;font-size:19px;color:#ffffff;">Order Delivered</h1>
<p style="margin:5px 0 0;font-size:13px;color:#c8e6c9;">Order #{{ $orderId }}</p>
</td>
</tr>

<!-- Body -->
<tr>
<td style="padding:28px 30px;">
<p style="margin:0 0 8px;font-size:15px;color:#222;">Hi {{ $name }},</p>
<p style="margin:0 0 15px;font-size:14px;color:#555;">
Your order <strong>#{{ $orderId }}</strong> has been successfully delivered.
</p>

<!-- Order Info Table -->
<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eeeeee;border-radius:6px;margin-bottom:18px;">
    <tr>
        <td style="padding:11px 16px;color:#888;font-size:13px;">Order Date</td>
        <td style="padding:11px 16px;text-align:right;font-size:13px;">{{ \Carbon\Carbon::parse($order_date)->format('d M Y, h:i A') }}</td>
    </tr>
    <tr>
        <td style="padding:11px 16px;color:#888;font-size:13px;">Delivered At</td>
        <td style="padding:11px 16px;text-align:right;font-size:13px;">{{ $rejection_time }}</td>
    </tr>
</table>

<!-- Items -->
<p style="font-size:12px;color:#999;margin-bottom:10px;text-transform:uppercase;">Items Summary</p>
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;border-bottom:1px solid #eee;">
    @foreach($items as $item)
    <tr>
        <td style="padding:8px 0;font-size:14px;">{{ $item['name'] ?? 'Item' }} (x{{ $item['quantity'] ?? 1 }})</td>
        <td style="text-align:right;padding:8px 0;font-size:14px;">₹{{ number_format($item['total'] ?? ($item['price'] * ($item['quantity'] ?? 1)), 2) }}</td>
    </tr>
    @endforeach
</table>

<!-- Price Breakdown -->
<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td style="padding:4px 0;font-size:13px;color:#777;">Subtotal</td>
        <td style="text-align:right;padding:4px 0;font-size:13px;">₹{{ number_format($subtotal, 2) }}</td>
    </tr>
    @if($platformFee > 0)
    <tr>
        <td style="padding:4px 0;font-size:13px;color:#777;">Platform Fee</td>
        <td style="text-align:right;padding:4px 0;font-size:13px;">₹{{ number_format($platformFee, 2) }}</td>
    </tr>
    @endif
    <tr>
        <td style="padding:4px 0;font-size:13px;color:#777;">GST ({{ $gstPercent }}%)</td>
        <td style="text-align:right;padding:4px 0;font-size:13px;">₹{{ number_format($gstAmount, 2) }}</td>
    </tr>
    @if(isset($discountAmount) && $discountAmount > 0)
    <tr>
        <td style="padding:4px 0;font-size:13px;color:#2e7d32;">Discount</td>
        <td style="text-align:right;padding:4px 0;font-size:13px;color:#2e7d32;">- ₹{{ number_format($discountAmount, 2) }}</td>
    </tr>
    @endif
    <tr>
        <td style="padding:15px 0;font-size:16px;font-weight:bold;color:#222;">Total Amount Paid</td>
        <td style="text-align:right;padding:15px 0;font-size:18px;font-weight:bold;color:#2e7d32;">₹{{ number_format($amount, 2) }}</td>
    </tr>
</table>

<!-- Refund Section -->
@if($refund_percentage > 0)
<table width="100%" style="background:#e8f5e9;border-left:4px solid #4caf50;margin-top:20px;">
    <tr>
        <td style="padding:15px;">
            <p style="margin:0;font-weight:bold;color:#2e7d32;">💰 Refund Initiated ({{ $refund_percentage }}%)</p>
            <p style="margin:10px 0;color:#1b5e20;font-size:13px;">{{ $refund_message ?: 'Your refund has been successfully initiated.' }}</p>
            <p style="margin:0;color:#333;font-size:12px;">Refund will be credited in 5-7 business days.</p>
        </td>
    </tr>
</table>
@endif

<p style="margin-top:25px;color:#999;font-size:13px;text-align:center;">
If you have any questions, contact our support team.
</p>
</td>
</tr>

<!-- Footer -->
<tr>
<td align="center" style="padding:16px;background:#fafafa;">
<p style="margin:0;font-size:11px;color:#bbb;">&copy; {{ date('Y') }} Nutrition App</p>
</td>
</tr>

</table>

</td>
</tr>
</table>

</body>
</html>