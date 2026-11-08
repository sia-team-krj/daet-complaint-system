<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #0B1F3A; margin: 0; }
        .email-wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; }
        .header { background: linear-gradient(135deg, #0B1F3A 0%, #12294d 100%); padding: 40px; text-align: center; }
        .header h1 { color: #C9A84C; font-family: Georgia, serif; margin: 0; }
        .body { padding: 40px; }
        .credentials { background: #F5F0E8; padding: 20px; border-radius: 6px; margin: 20px 0; }
        .credentials strong { color: #0B1F3A; }
        .footer { background: #F5F0E8; padding: 20px; text-align: center; font-size: 12px; color: #6B7280; }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="header">
            <h1>Welcome to Daet Listens</h1>
            <p style="color: rgba(255,255,255,0.7); margin: 10px 0 0;">Staff Portal Access</p>
        </div>
        <div class="body">
            <p>{{ __('mail.welcome_hello') }} {{ $user->first_name }},</p>
            <p>{{ __('mail.welcome_body') }}</p>
            <div class="credentials">
                <p><strong>{{ __('mail.welcome_email') }}:</strong> {{ $user->email }}</p>
                <p><strong>{{ __('mail.welcome_temp') }}:</strong> {{ $tempPassword }}</p>
            </div>
            <p>{{ __('mail.welcome_login') }} <a href="{{ url('/login') }}" style="color: #C9A84C;">{{ url('/login') }}</a></p>
            <p>{{ __('mail.welcome_contact') }}</p>
        </div>
        <div class="footer">
            Municipality of Daet · Camarines Norte<br>
            Daet Listens - Official Transparency Portal
        </div>
    </div>
</body>
</html>
