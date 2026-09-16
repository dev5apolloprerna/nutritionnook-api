<!DOCTYPE html>
<html>
<head>
    <title>Order Accepted</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #28a745; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { padding: 30px; background-color: #f9f9f9; border: 1px solid #ddd; }
        .success-icon { font-size: 48px; text-align: center; margin: 20px 0; }
        .order-info { background-color: #fff; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #28a745; }
        .item { border-bottom: 1px solid #eee; padding: 10px 0; }
        .item:last-child { border-bottom: none; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .btn { background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Order Accepted!</h1>
        </div>
        
        <div class="content">
            <div class="success-icon">👨‍🍳</div>
            
            <h2 style="text-align: center;">Good News, {{ $user->name }}!</h2>
            
            <p style="text-align: center;">Your order has been accepted by the chef and will be prepared soon.</p>
            
            <div class="order-info">
                <h3>Order Details:</h3>
                <p><strong>Order ID:</strong> #{{ $order_id }}</p>
                <p><strong>Chef:</strong> {{ $chef->name ?? 'N/A' }}</p>
                <p><strong>Order Date:</strong> {{ \Carbon\Carbon::parse($order_date)->format('d M Y') }}</p>
                <p><strong>Accepted Time:</strong> {{ $accept_time }}</p>
                <p><strong>Pick Up Address:</strong> {{ $address }}</p>
                
                <h4 style="margin-top: 20px;">Items:</h4>
                @foreach($items as $item)
                <div class="item">
                    <p><strong>{{ $item['name'] ?? 'Item' }}</strong> x {{ $item['quantity'] ?? 1 }}</p>
                    <p>₹{{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2) }}</p>
                </div>
                @endforeach
                
                <p style="font-size: 18px; font-weight: bold; margin-top: 20px; text-align: right;">
                    Total: ₹{{ number_format($amount, 2) }}
                </p>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <p>Your food will be prepared fresh and delivered on time!</p>
               
            </div>
            
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Food App. All rights reserved.</p>
            <p>Need help? Contact us at support@foodapp.com</p>
        </div>
    </div>
</body>
</html>