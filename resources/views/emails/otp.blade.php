<!DOCTYPE html>
<html>
<head>
    <title>Login OTP</title>
</head>
<body>
    <h2>Hello {{ $user->name ?? 'User' }}!</h2>
    <p>Your login OTP for Food App is: <strong>{{ $otp }}</strong></p>
    <p>This OTP will expire in 30 minutes.</p>
    <p>If you didn't request this, please ignore this email.</p>
    <br>
    <p>Thanks,<br>Food App Team</p>
</body>
</html>