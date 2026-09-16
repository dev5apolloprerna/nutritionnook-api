<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
        .order-details { background-color: #f9f9f9; padding: 20px; margin: 20px 0; }
        .item { border-bottom: 1px solid #ddd; padding: 10px 0; }
        .total { font-size: 18px; font-weight: bold; color: #4CAF50; }
        .footer { text-align: center; padding: 20px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Order Confirmed!</h1>
        </div>
        
        <div class="order-details">
            <h2>Hello {{ $user->name }}!</h2>
            <p>Your order has been placed successfully.</p>
            
            <p><strong>Order ID:</strong> #{{ $order_id }}</p>
            <p><strong>Chef:</strong> {{ $chef->name ?? 'N/A' }}</p>
            <p><strong>Delivery Date:</strong> {{ $order_date }}</p>
            <p><strong>Delivery Address:</strong> {{ $address }}</p>
            
            <h3>Order Items:</h3>
            @foreach($items as $item)
            <div class="item">
                <p><strong>{{ $item['name'] }}</strong> x {{ $item['quantity'] }}</p>
                <p>₹{{ number_format($item['price'] * $item['quantity'], 2) }}</p>
            </div>
            @endforeach
            
            <p><strong>Subtotal:</strong> ₹{{ number_format($subtotal, 2) }}</p>
            <p><strong>GST:</strong> ₹{{ number_format($gst, 2) }}</p>
            @if($discount > 0)
            <p><strong>Discount:</strong> -₹{{ number_format($discount, 2) }}</p>
            @endif
            <p class="total"><strong>Total Amount:</strong> ₹{{ number_format($final_amount, 2) }}</p>
            
            <p><strong>Payment Status:</strong> Pending</p>
            <p>Please complete your payment using Razorpay.</p>
        </div>
        
        <div class="footer">
            <p>Thank you for ordering with Food App!</p>
        </div>
    </div>
</body>
</html>