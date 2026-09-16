<!DOCTYPE html>
<html>
<head>
    <title>Order Cancelled</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="padding:30px 0;">
<tr>
<td align="center">

<table width="540" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;">

<!-- Header -->
<tr>
<td align="center" style="background:#dc3545;padding:25px;">
<h1 style="margin:0;color:#fff;">❌ Order Cancelled</h1>
</td>
</tr>

<!-- Body -->
<tr>
<td style="padding:30px;">

<p>Hi {{ $chef->name ?? 'Chef' }},</p>

<p>
Order <strong>#{{ $orderId }}</strong> has been cancelled by admin.
</p>

<p style="color:#dc3545;font-weight:bold;">
This order has been successfully cancelled and removed from your active orders.
</p>

<p style="margin-top:15px;">
No further action is required from your side.
</p>

<p style="margin-top:20px;">
If you have already started preparing this order, please stop and contact the admin team.
</p>

<p style="margin-top:20px;">
We apologize for the inconvenience caused.
</p>

</td>
</tr>

<!-- Footer -->
<tr>
<td align="center" style="padding:20px;background:#fafafa;">
<p style="margin:0;color:#999;">© {{ date('Y') }} Food App</p>
</td>
</tr>

</table>

</td>
</tr>
</table>

</body>
</html>