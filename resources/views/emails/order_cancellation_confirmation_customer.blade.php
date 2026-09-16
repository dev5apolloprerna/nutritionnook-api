<!DOCTYPE html>
<html>
<head>
    <title>Order Cancellation Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #ffc107;
            color: #000;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 30px;
        }
        .refund-box {
            background-color: #e2f0ff;
            border: 1px solid #b6d4fe;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background-color: #007bff;
            color: white !important;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #777;
            font-size: 14px;
            background-color: #fafafa;
        }
    </style>
</head>
<body>

<div class="container">
    
    <div class="header">
        <h1>⚠️ Order Cancelled</h1>
    </div>

    <div class="content">
        <h2>Hi {{ $user->name }},</h2>

        <p>
            Your order <strong>#{{ $order_id }}</strong> has been successfully cancelled.
        </p>

        <div class="refund-box">
    <h3>💰 Refund Details</h3>

    @if($refund_percentage > 0)
        <p>
            You will receive a <strong>{{ $refund_percentage }}% refund</strong>.
        </p>

        <p>
            {{ $refund_message }}
        </p>

        <p>
            Your refund has been <strong>successfully initiated</strong> and is currently being processed.
        </p>

        <p>
            The amount will be credited to your original payment method within 
            <strong>5 to 7 business days</strong>, depending on your bank or payment provider.
        </p>

        <p>
            If you do not receive the refund within this timeframe, we recommend checking with your bank or contacting our support team.
        </p>
    @else
        <p>
            No refund is applicable for this order.
        </p>
    @endif
</div>

        <p>
            You can place a new order anytime from our platform.
        </p>

    </div>

    <div class="footer">
        <p>Thank you for choosing us </p>
        <p>Need help? Contact our support team anytime.</p>
    </div>

</div>

</body>
</html>