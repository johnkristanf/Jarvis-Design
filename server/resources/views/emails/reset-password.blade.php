<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset Your Password</title>
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
        <p>You requested to reset your password for your Jarvis Designs account.</p>
        <p>Click the button below to choose a new password. This link will expire in 60 minutes.</p>

        <a href="{{ $resetUrl }}" class="btn" style="color: white !important;">Reset Password</a>

        <p>If the button doesn't work, copy and paste the link below into your browser:</p>
        <p><a href="{{ $resetUrl }}">{{ $resetUrl }}</a></p>

        <div class="footer">
            <p>If you didn't request a password reset, you can safely ignore this email.</p>
            <p>— JarvisDesigns</p>
        </div>
    </div>
</body>

</html>
