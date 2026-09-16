<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Food App</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #ff6b6b; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; background-color: #f9f9f9; }
        .otp-box { background-color: #fff; padding: 20px; text-align: center; font-size: 32px; 
                   font-weight: bold; color: #ff6b6b; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Food App! 🍽️</h1>
        </div>
        
        <div class="content">
            <h2>Hello {{ $user->name }}!</h2>
            
            <p>Thank you for registering with Food App. Please verify your email address using the OTP below:</p>
            
            <div class="otp-box">
                {{ $otp }}
            </div>
            
            <p><strong>This OTP will expire in 10 minutes.</strong></p>
            
            <p>If you didn't register for Food App, please ignore this email.</p>
            
            <hr style="border: 1px solid #eee; margin: 30px 0;">
            
            <h3>What's Next? 🚀</h3>
            <ul>
                <li>Verify your email with the OTP above</li>
                <li>Complete your profile</li>
                <li>Start ordering delicious food!</li>
            </ul>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Food App. All rights reserved.</p>
            <p>This is an automated message, please do not reply.</p>
        </div>
    </div>
</body>
</html>