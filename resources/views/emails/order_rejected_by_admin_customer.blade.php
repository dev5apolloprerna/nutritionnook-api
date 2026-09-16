<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Cancelled</title>
</head>
<body style="margin:0;padding:0;background:#f2f2f2;font-family:Arial,sans-serif;">

@php
    $refund_percentage = $refund_percentage ?? 0;
    $refund_message = $refund_message ?? '';
@endphp

<table width="100%" cellpadding="0" cellspacing="0" style="padding:30px 0;">
<tr>
<td align="center">

<table width="540" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;">

<!-- Header -->
<tr>
<td align="center" style="background:#d32f2f;padding:28px 30px;">
<p style="margin:0 0 6px;font-size:30px;">❌</p>
<h1 style="margin:0;font-size:19px;color:#ffffff;">Order Cancelled</h1>
<p style="margin:5px 0 0;font-size:13px;color:#ef9a9a;">Order #{{ $orderId }}</p>
</td>
</tr>

<!-- Body -->
<tr>
<td style="padding:28px 30px;">

<p style="margin:0 0 8px;font-size:15px;color:#222;">Hi {{ $name }},</p>

<p style="margin:0 0 15px;font-size:14px;color:#555;">
Your order <strong>#{{ $orderId }}</strong> has been rejected by chef.
</p>

<p style="margin:0 0 20px;font-size:14px;color:#d32f2f;font-weight:bold;">
Your order is successfully cancelled. Refund will be initiated shortly.
</p>

<!-- Order Summary -->
<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eeeeee;border-radius:6px;margin-bottom:18px;">
<tr style="background:#fafafa;">
<td colspan="2" style="padding:10px 16px;font-size:11px;color:#999;">Order Summary</td>
</tr>

<tr>
<td style="padding:11px 16px;color:#888;">Order ID</td>
<td style="padding:11px 16px;text-align:right;">#{{ $orderId }}</td>
</tr>

<tr>
<td style="padding:11px 16px;color:#888;">Order Date</td>
<td style="padding:11px 16px;text-align:right;">
{{ \Carbon\Carbon::parse($order_date)->format('d M Y, h:i A') }}
</td>
</tr>

<tr>
<td style="padding:11px 16px;color:#888;">Cancelled At</td>
<td style="padding:11px 16px;text-align:right;">{{ $rejection_time }}</td>
</tr>

<tr>
<td style="padding:11px 16px;color:#888;">Total Amount</td>
<td style="padding:11px 16px;text-align:right;">
₹{{ number_format($amount, 2) }}
</td>
</tr>
</table>

<!-- Items -->
@if(!empty($items))
<p style="font-size:12px;color:#999;">Items</p>
<table width="100%" cellpadding="0">
@foreach($items as $item)
<tr>
<td style="padding:6px 0;">
{{ $item['name'] ?? 'Item' }} 
@if(isset($item['quantity'])) (x{{ $item['quantity'] }}) @endif
</td>
<td style="text-align:right;">
@if(isset($item['price'])) ₹{{ number_format($item['price'], 2) }} @endif
</td>
</tr>
@endforeach
</table>
@endif

<!-- Refund Section -->
@if($refund_percentage > 0)

<table width="100%" style="background:#fffde7;border-left:4px solid #ffc107;margin-top:20px;">
<tr>
<td style="padding:15px;">

<p style="margin:0;font-weight:bold;color:#f57c00;">
💰 Refund Initiated ({{ $refund_percentage }}%)
</p>

<p style="margin:10px 0;color:#795548;">
{{ $refund_message ?: 'Your refund has been successfully initiated.' }}
</p>

<p style="margin:0;color:#333;">
The refund amount will be credited to your original payment method within 
<strong>5-7 business days</strong>.
</p>

<p style="margin-top:10px;font-size:12px;color:#888;">
Note: Depending on your bank or payment provider, it may take additional time to reflect in your account.
</p>

</td>
</tr>
</table>

@else

<table width="100%" style="background:#f5f5f5;border-left:4px solid #bdbdbd;margin-top:20px;">
<tr>
<td style="padding:15px;">
<p style="margin:0;color:#757575;">
No refund is applicable for this order as per our policy.
</p>
</td>
</tr>
</table>

@endif

<p style="margin-top:20px;color:#999;">
If you have any questions, feel free to contact our support team.
</p>

</td>
</tr>

<!-- Footer -->
<tr>
<td align="center" style="padding:16px;background:#fafafa;">
<p style="margin:0;font-size:12px;color:#bbb;">
© {{ date('Y') }} Nutrition App
</p>
</td>
</tr>

</table>

</td>
</tr>
</table>

</body>
</html>