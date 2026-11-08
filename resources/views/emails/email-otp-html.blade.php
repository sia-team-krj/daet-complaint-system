<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify your {{ $appName }} email</title>
</head>
<body style="margin:0;background:#f5f0e8;color:#0b1f3a;font-family:Arial,sans-serif;">
    <div style="max-width:600px;margin:0 auto;padding:40px 20px;">
        <div style="background:#1B3A61;border:1px solid rgba(201,168,76,.45);border-radius:8px;padding:36px;">
            <p style="margin:0 0 12px;color:#c9a84c;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;">Account security</p>
            <h1 style="margin:0 0 18px;color:#fff;font-size:32px;line-height:1.1;">Verify your {{ $appName }} email</h1>

            @if ($purpose === 'email_change')
                <p style="margin:0 0 22px;color:rgba(255,255,255,.72);font-size:15px;line-height:1.6;">We received a request to change the email address on your account. Enter the code below to confirm the new address.</p>
            @else
                <p style="margin:0 0 22px;color:rgba(255,255,255,.72);font-size:15px;line-height:1.6;">Use the code below to verify your email address and activate your account.</p>
            @endif

            <p style="margin:0 0 10px;color:rgba(255,255,255,.6);font-size:13px;">Your verification code</p>
            <div style="margin:0 0 22px;padding:16px;text-align:center;color:#0b1f3a;background:#c9a84c;border-radius:4px;font-size:30px;font-weight:700;letter-spacing:.28em;">{{ $code }}</div>
            <p style="margin:0 0 24px;color:rgba(255,255,255,.52);font-size:12px;line-height:1.5;">This code expires in {{ $expiryMinutes }} minutes. Do not share it with anyone.</p>

            <a href="{{ route('email.verify', ['purpose' => $purpose]) }}" style="display:inline-block;padding:13px 20px;color:#0b1f3a;background:#c9a84c;border-radius:4px;font-size:12px;font-weight:700;text-decoration:none;">Open verification page</a>
            <p style="margin:26px 0 0;color:rgba(255,255,255,.4);font-size:12px;line-height:1.5;">If you did not request this, you can safely ignore this email.</p>
        </div>
    </div>
</body>
</html>
