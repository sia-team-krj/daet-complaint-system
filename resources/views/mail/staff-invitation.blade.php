<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Daet Listens Staff Invitation</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #0B1F3A;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .email-wrapper { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .email-header {
            background: linear-gradient(135deg, #0B1F3A 0%, #12294d 100%);
            padding: 40px 30px;
            text-align: center;
        }
        .logo { max-width: 80px; height: auto; margin-bottom: 20px; }
        .brand-title {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 28px;
            color: #C9A84C;
            margin: 0;
            letter-spacing: -0.5px;
        }
        .brand-subtitle {
            font-size: 12px;
            color: rgba(255,255,255,0.6);
            margin-top: 8px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        .email-body { padding: 40px 30px; background-color: #ffffff; }
        .greeting { font-size: 20px; color: #0B1F3A; margin-bottom: 20px; font-weight: 600; }
        .message { font-size: 15px; color: #4B5563; line-height: 1.7; margin-bottom: 20px; }
        .assignment {
            background: #F5F0E8;
            border-left: 3px solid #C9A84C;
            padding: 18px 20px;
            border-radius: 4px;
            margin: 26px 0;
        }
        .assignment-label {
            font-size: 11px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #6B7280;
            margin: 0 0 6px;
        }
        .assignment-value { font-size: 17px; color: #0B1F3A; font-weight: 700; margin: 0; }
        .steps { margin: 26px 0; padding: 0; list-style: none; }
        .steps li {
            position: relative;
            padding-left: 30px;
            margin-bottom: 12px;
            font-size: 14px;
            color: #4B5563;
            line-height: 1.6;
        }
        .steps li::before {
            content: counter(list-item);
            position: absolute;
            left: 0;
            top: 1px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #C9A84C;
            color: #0B1F3A;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .button-wrapper { text-align: center; margin: 32px 0; }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #C9A84C, #E2C06A);
            color: #0B1F3A;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            box-shadow: 0 4px 16px rgba(201,168,76,0.3);
        }
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(201,168,76,0.3), transparent);
            margin: 30px 0;
        }
        .fallback { font-size: 13px; color: #6B7280; text-align: center; margin-top: 20px; }
        .fallback a { color: #C9A84C; text-decoration: none; word-break: break-all; }
        .expiry-note {
            font-size: 12px;
            color: #9CA3AF;
            text-align: center;
            margin-top: 25px;
            font-style: italic;
        }
        .email-footer { background-color: #F5F0E8; padding: 25px 30px; text-align: center; }
        .footer-text { font-size: 12px; color: #6B7280; margin-bottom: 8px; }
        .footer-brand { font-size: 14px; color: #0B1F3A; font-weight: 600; font-family: 'Georgia', 'Times New Roman', serif; }
        .footer-seal { font-size: 10px; color: #9CA3AF; margin-top: 12px; letter-spacing: 0.05em; }
        @media only screen and (max-width: 600px) {
            .email-body { padding: 30px 20px; }
            .button { padding: 14px 30px; font-size: 13px; }
            .brand-title { font-size: 24px; }
        }
    </style>
</head>
<body>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td>
                <div class="email-wrapper">
                    <div class="email-header">
                        <img src="{{ asset('images/lgulogo.webp') }}" alt="LGU Daet" class="logo">
                        <h1 class="brand-title">Daet Listens</h1>
                        <p class="brand-subtitle">Official Transparency Portal</p>
                    </div>

                    <div class="email-body">
                        <p class="greeting">Hello {{ $inviteeName ?: 'there' }},</p>

                        <p class="message">
                            You have been invited to join the Daet Listens staff portal. Use the secure
                            link below to create your account and choose your own password.
                        </p>

                        <div class="assignment">
                            <p class="assignment-label">Assigned department</p>
                            <p class="assignment-value">{{ $departmentName }}</p>
                        </div>

                        <ol class="steps">
                            <li>Open the invitation link on this page.</li>
                            <li>Confirm your name and create a password.</li>
                            <li>Verify the email address we send you.</li>
                        </ol>

                        <div class="button-wrapper">
                            <a href="{{ $url }}" class="button">Accept Invitation</a>
                        </div>

                        <p class="expiry-note">
                            This link expires on {{ $expiresAt->format('M j, Y g:i A') }} and can only be used once.
                        </p>

                        <div class="divider"></div>

                        <p class="fallback">
                            If the button doesn't work, copy and paste this link into your browser:<br>
                            <a href="{{ $url }}">{{ $url }}</a>
                        </p>

                        <p class="fallback" style="margin-top: 30px;">
                            This invitation was issued for <strong>{{ $inviteeEmail }}</strong> and will only
                            create an account for that address. If you were not expecting this, you can
                            safely ignore this email.
                        </p>
                    </div>

                    <div class="email-footer">
                        <p class="footer-text">Municipality of Daet · Camarines Norte</p>
                        <p class="footer-brand">Daet Listens</p>
                        <p class="footer-seal">Republic Act 6713 · FOI Compliant</p>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
