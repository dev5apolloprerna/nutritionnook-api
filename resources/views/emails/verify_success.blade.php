<!DOCTYPE html>
<html>
<head>
    <title>Login Successful - Food App</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { padding: 30px; background-color: #f9f9f9; border: 1px solid #ddd; }
        .success-icon { font-size: 48px; text-align: center; margin: 20px 0; }
        .info-box { background-color: #fff; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #4CAF50; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .btn { background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                @if($type == 'chef')
                    👨‍🍳 Welcome Back Chef!
                @else
                    🍽️ Welcome Back!
                @endif
            </h1>
        </div>
        
        <div class="content">
            <div class="success-icon">✅</div>
            
            <h2 style="text-align: center;">Login Successful</h2>
            
            <p>Hello <strong>{{ $user->name }}</strong>,</p>
            
            <p>You have successfully logged in to your Food App account.</p>
            
            <div class="info-box">
                <h3>Login Details:</h3>
                <p><strong>Account Type:</strong> {{ ucfirst($type) }}</p>
                <p><strong>Login Time:</strong> {{ $login_time }}</p>
                <p><strong>Phone Number:</strong> {{ $user->phone_number }}</p>
                @if(!empty($user->email))
                <p><strong>Email:</strong> {{ $user->email }}</p>
                @endif
            </div>
            
            
            <p style="color: #666; font-size: 14px; margin-top: 30px;">
                If you didn't perform this login, please contact our support team immediately.
            </p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Food App. All rights reserved.</p>
            <p>Need help? Contact us at support@foodapp.com</p>
        </div>
    </div>
</body>
</html>