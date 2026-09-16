<!DOCTYPE html>
<html>
<head>
    <title>Order Rejected</title>
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
            background-color: #dc3545;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 30px;
        }
        .refund-box {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background-color: #dc3545;
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
        <h1>❌ Order Rejected</h1>
    </div>

    <div class="content">
        <h2>Hi {{ $user->name }},</h2>

        <p>
            We regret to inform you that your order 
            <strong>#{{ $order_id }}</strong> has been rejected by the chef.
        </p>

        <div class="refund-box">
            <h3>💰 Refund Status</h3>

            <p>
                <strong>{{ $refund_message ?: 'No refund applicable for this order.' }}</strong>
            </p>

            @if($refund_percentage > 0)
                <p>
                    Your refund has been initiated and will be credited to your original 
                    payment method within <strong>5-7 business days</strong>.
                </p>
            @endif
        </div>

        <p>
            <strong>Reason for rejection:</strong><br>
            {{ $reason }}
        </p>

      
    </div>

    <div class="footer">
        <p>Thank you for using our service ❤️</p>
        <p>If you have any questions, feel free to contact our support team.</p>
    </div>

</div>

</body>
</html>