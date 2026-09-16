<!DOCTYPE html>
<html>
<head>
    <title>Order Canceled by Customer</title>
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
            color: #333;
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
        <h1>⚠️ Order Canceled</h1>
    </div>
    
    <div class="content">
        <h2>Hello {{ $chef->name }},</h2>
        
        <p>
            Order <strong>#{{ $order_id }}</strong> has been canceled by the customer.
        </p>
        
        <p><strong>Customer:</strong> {{ $user->name }}</p>
        <p><strong>Cancellation Time:</strong> {{ $cancellation_time }}</p>
        <p><strong>Reason:</strong> {{ $reason }}</p>
        
        @if($refund_percentage > 0)
        <div class="refund-box">
    <h3>💰 Refund Status</h3>

    <p>
        Your order has been successfully cancelled. The refund has been initiated and will be processed shortly.
    </p>

    <p>
        A <strong>{{ $refund_percentage }}% refund</strong> has been initiated for this order.
    </p>

    <p>
        The refund is currently being processed and will be credited to the customer's original payment method within 
        <strong>5-7 business days</strong>.
    </p>

   
</div>
        @endif
        
        <p>
            This order has been removed from your active orders.
        </p>
    </div>

    <div class="footer">
        <p>Thank you for being part of our platform ❤️</p>
    </div>

</div>

</body>
</html>