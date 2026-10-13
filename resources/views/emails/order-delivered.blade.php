<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Delivered #{{ $orderId }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; color: #333; }
        .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .header { background-color: #28a745; color: #ffffff; padding: 30px; text-align: center; }
        .content { padding: 30px; }
        .order-details { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .order-details th { text-align: left; border-bottom: 2px solid #eee; padding: 10px; color: #666; }
        .order-details td { padding: 10px; border-bottom: 1px solid #eee; }
        .total-row { font-weight: bold; font-size: 16px; color: #333; }
        .final-total { font-weight: bold; font-size: 20px; color: #28a745; border-top: 2px solid #28a745 !important; }
        .footer { background-color: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #777; }
        .text-right { text-align: right; }
        .text-success { color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Order Delivered! 🎉</h1>
            <p>Your meal has arrived</p>
        </div>

        <div class="content">
            <p>Hi <strong>{{ $customerName }}</strong>,</p>
            <p>Good news! Your order <strong>#{{ $orderId }}</strong> has been delivered successfully.</p>
            
            <table class="order-details">
                <tbody>
                    <!-- Final Total -->
                    <tr class="final-total">
                        <td colspan="2" class="text-right" style="padding: 15px;">Total Paid:</td>
                        <td class="text-right" style="padding: 15px;">₹{{ number_format($amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <p style="margin-top: 30px;">
                <strong>Note:</strong> We have attached the official tax invoice (PDF) with this email for your reference.
            </p>

            <p>Thank you for ordering with us!</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Nutrition Nook. All rights reserved.</p>
        </div>
    </div>
</body>
</html>