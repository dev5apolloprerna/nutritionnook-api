<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
</head>
<body style="margin:0;padding:20px;background:#f5f5f5;font-family:Arial,sans-serif;font-size:14px;color:#333;">

<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td align="center">
      <table width="500" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:6px;overflow:hidden;">

        <tr>
          <td style="background:#2196F3;padding:24px;text-align:center;">
            <div style="font-size:24px;margin-bottom:6px;">🔔</div>
            <div style="font-size:18px;font-weight:bold;color:#fff;">Order Status Updated</div>
          </td>
        </tr>

        <tr>
          <td style="padding:24px;">
            <p style="margin:0 0 12px;">Hi {{ $name }},</p>
            <p style="margin:0 0 20px;color:#555;line-height:1.6;">Your order <strong>#{{ $orderId }}</strong> status has been updated.</p>

            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9f9f9;border-radius:4px;margin-bottom:20px;">
              <tr>
                <td style="padding:12px 16px;border-bottom:1px solid #eee;color:#888;">Order ID</td>
                <td style="padding:12px 16px;border-bottom:1px solid #eee;text-align:right;font-weight:bold;color:#2196F3;">#{{ $orderId }}</td>
              </tr>
              <tr>
                <td style="padding:12px 16px;border-bottom:1px solid #eee;color:#888;">Status</td>
                <td style="padding:12px 16px;border-bottom:1px solid #eee;text-align:right;">
                  <span style="display:inline-block;padding:3px 12px;background:#e3f2fd;color:#1565c0;border-radius:12px;font-weight:bold;font-size:13px;">{{ ucfirst($status) }}</span>
                </td>
              </tr>
              <tr>
                <td style="padding:12px 16px;color:#888;">Amount</td>
                <td style="padding:12px 16px;text-align:right;font-weight:bold;">&#8377;{{ number_format($amount, 2) }}</td>
              </tr>
            </table>

            <p style="margin:0;color:#999;font-size:13px;">If you have any questions, please contact our support team.</p>
          </td>
        </tr>

        <tr>
          <td style="padding:16px;background:#f9f9f9;border-top:1px solid #eee;text-align:center;">
            <span style="font-size:12px;color:#bbb;">&copy; {{ date('Y') }} Food App. All rights reserved.</span>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
