<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Verify Your Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7fafc;
            padding: 40px;
        }

        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin-top: 20px;
            background-color: #111827;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <h2>Hello {{ $emailTo }},</h2>
        <p>Thanks for signing up with your email: <strong>{{ $emailTo }}</strong>.</p>
        <p>Please click the button below to verify your email address and activate your account.</p>

        <a href="{{ $verificationUrl }}" class="btn" style="color: white !important;">Verify Email</a>

        <p>If the button doesn't work, copy and paste the link below into your browser:</p>
        <p><a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a></p>

        <div class="footer">
            <p>If you didn’t create an account, no further action is required.</p>
            <p>— JarvisDesigns</p>
        </div>
    </div>
</body>

</html>