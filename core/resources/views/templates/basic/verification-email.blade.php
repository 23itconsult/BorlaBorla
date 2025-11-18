<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borlaborla Email Verification</title>
</head>
<body>
    <h2>Hello {{ $data['firstname'] }},</h2>
    <p>Thank you for registering with us. Here is your verification code:</p>
    
    <h3> {{ $data['ver_code'] }}</h3>
    
    <p>Please enter this code in the verification page to complete your registration.</p>
</body>
</html>