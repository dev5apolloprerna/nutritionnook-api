<!DOCTYPE html>
<html>
<head>
    <title>Order Acceptance Confirmation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #ff6b6b; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { padding: 30px; background-color: #f9f9f9; border: 1px solid #ddd; }
        .order-info { background-color: #fff; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👨‍🍳 Order Accepted</h1>
        </div>
        
        <div class="content">
            <h2>Hello {{ $chef->name }}!</h2>
            
            <p>You have successfully accepted order #{{ $order_id }}.</p>
            
            <div class="order-info">
                <h3>Order Summary:</h3>
                <p><strong>Customer:</strong> {{ $user->name }}</p>
                <p><strong>Phone:</strong> {{ $user->phone_number }}</p>
                <p><strong>Order Date:</strong> {{ \Carbon\Carbon::parse($order_date)->format('d M Y') }}</p>
                <p><strong>Accepted Time:</strong> {{ $accept_time }}</p>
                
                <h4 style="margin-top: 20px;">Items to Prepare:</h4>
                @foreach($items as $item)
                <div style="border-bottom: 1px solid #eee; padding: 5px 0;">
                    <p><strong>{{ $item['name'] ?? 'Item' }}</strong> x {{ $item['quantity'] ?? 1 }}</p>
                </div>
                @endforeach
                
                <p style="font-size: 18px; font-weight: bold; margin-top: 20px; text-align: right;">
                    Total Amount: ₹{{ number_format($amount, 2) }}
                </p>
            </div>
            
            <div style="background-color: #fff3cd; border: 1px solid #ffeeba; padding: 15px; border-radius: 5px; margin-top: 20px;">
                <p style="color: #856404; margin: 0;">
                    <strong>⚠️ Reminder:</strong> Please ensure timely preparation and quality of food.
                </p>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="{{ url('/chef/orders/' . $order_id) }}" style="background-color: #ff6b6b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">View Order Details</a>
            </div>
        </div>
        
        <div class="footer">
            <p>Thank you for providing excellent service!</p>
        </div>
    </div>
</body>
</html>