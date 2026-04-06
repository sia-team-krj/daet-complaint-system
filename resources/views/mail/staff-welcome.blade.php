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
            <p>Hello {{ $user->first_name }},</p>
            <p>An admin has created a staff account for you on the Daet Listens complaint portal.</p>
            <div class="credentials">
                <p><strong>Email:</strong> {{ $user->email }}</p>
                <p><strong>Temporary Password:</strong> {{ $tempPassword }}</p>
            </div>
            <p>Please log in at <a href="{{ url('/login') }}" style="color: #C9A84C;">{{ url('/login') }}</a> and change your password after your first login.</p>
            <p>If you have any questions, please contact your department administrator.</p>
        </div>
        <div class="footer">
            Municipality of Daet · Camarines Norte<br>
            Daet Listens — Official Transparency Portal
        </div>
    </div>
</body>
</html>
