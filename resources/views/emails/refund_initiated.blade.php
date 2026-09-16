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
          <td style="background:#388E3C;padding:24px;text-align:center;">
            <div style="font-size:24px;margin-bottom:6px;">💰</div>
            <div style="font-size:18px;font-weight:bold;color:#fff;">Refund Initiated</div>
          </td>
        </tr>

        <tr>
          <td style="padding:24px;">
            <p style="margin:0 0 12px;">Hi {{ $name }},</p>
            <p style="margin:0 0 20px;color:#555;line-height:1.6;">Your refund for order <strong>#{{ $orderId }}</strong> has been initiated successfully.</p>

            <!-- Refund amount highlight -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f8e9;border-radius:4px;margin-bottom:20px;">
              <tr>
                <td style="padding:18px;text-align:center;">
                  <div style="font-size:12px;color:#7cb342;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">Refund Amount</div>
                  <div style="font-size:26px;font-weight:bold;color:#2e7d32;">&#8377;{{ number_format($refund_amount, 2) }}</div>
                  <div style="font-size:12px;color:#558b2f;margin-top:4px;">{{ $refund_percentage }}% of order value</div>
                </td>
              </tr>
            </table>

            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9f9f9;border-radius:4px;margin-bottom:20px;">
              <tr>
                <td style="padding:12px 16px;border-bottom:1px solid #eee;color:#888;">Order ID</td>
                <td style="padding:12px 16px;border-bottom:1px solid #eee;text-align:right;font-weight:bold;color:#388E3C;">#{{ $orderId }}</td>
              </tr>
              <tr>
                <td style="padding:12px 16px;border-bottom:1px solid #eee;color:#888;">Refund %</td>
                <td style="padding:12px 16px;border-bottom:1px solid #eee;text-align:right;font-weight:bold;">{{ $refund_percentage }}%</td>
              </tr>
              <tr>
                <td style="padding:12px 16px;color:#888;">Expected Credit</td>
                <td style="padding:12px 16px;text-align:right;font-weight:bold;">5&ndash;7 Business Days</td>
              </tr>
            </table>

            <!-- Note -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#fffde7;border-left:3px solid #ffc107;margin-bottom:20px;">
              <tr>
                <td style="padding:12px 14px;">
                  <div style="font-size:13px;color:#795548;line-height:1.5;">
                    {{ $refund_message }} Refund will be credited to your original payment method. Processing time may vary by bank.
                  </div>
                </td>
              </tr>
            </table>

            <p style="margin:0;color:#999;font-size:13px;">If you do not receive your refund within 7 business days, please contact our support team with your order ID.</p>
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
