<!DOCTYPE html>
<html>
<head>
    <title>New Order Received</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #ff6b6b; color: white; padding: 20px; text-align: center; }
        .order-details { background-color: #f9f9f9; padding: 20px; margin: 20px 0; }
        .item { border-bottom: 1px solid #ddd; padding: 10px 0; }
        .footer { text-align: center; padding: 20px; color: #666; }
        .badge { background-color: {{ $is_future_order ? '#ffc107' : '#28a745' }}; color: white; padding: 5px 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👨‍🍳 New Order Received!</h1>
        </div>
        
        <div class="order-details">
            <h2>Hello {{ $chef->name }}!</h2>
            
            <div style="text-align: center; margin-bottom: 20px;">
                <span class="badge">{{ $is_future_order ? '📅 Pre-order' : '🔴 Immediate' }}</span>
            </div>
            
            <p><strong>Order ID:</strong> #{{ $order_id }}</p>
            <p><strong>Customer:</strong> {{ $user->name }}</p>
            <p><strong>Phone:</strong> {{ $user->phone_number }}</p>
            <p><strong>Delivery Date:</strong> {{ $order_date }}</p>
            <p><strong>Delivery Address:</strong> {{ $address }}</p>
            
            <h3>Order Items:</h3>
            @foreach($items as $item)
            <div class="item">
                <p><strong>{{ $item['name'] }}</strong> x {{ $item['quantity'] }}</p>
            </div>
            @endforeach
            
            <p style="font-size: 18px; font-weight: bold;">Total: ₹{{ number_format($final_amount, 2) }}</p>
            
            <p style="margin-top: 30px; text-align: center;">
                <a href="{{ url('/chef/orders/' . $order_id) }}" style="background-color: #ff6b6b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">View Order Details</a>
            </p>
        </div>
        
        <div class="footer">
            <p>Please prepare the order on time!</p>
        </div>
    </div>
</body>
</html>