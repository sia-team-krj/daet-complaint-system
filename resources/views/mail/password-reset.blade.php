<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password - Daet Listens</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #0B1F3A;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .email-header {
            background: linear-gradient(135deg, #0B1F3A 0%, #12294d 100%);
            padding: 40px 30px;
            text-align: center;
        }
        .logo {
            max-width: 80px;
            height: auto;
            margin-bottom: 20px;
        }
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
        .email-body {
            padding: 40px 30px;
            background-color: #ffffff;
        }
        .greeting {
            font-size: 20px;
            color: #0B1F3A;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .message {
            font-size: 15px;
            color: #4B5563;
            line-height: 1.7;
            margin-bottom: 30px;
        }
        .button-wrapper {
            text-align: center;
            margin: 35px 0;
        }
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
        .button:hover {
            box-shadow: 0 6px 24px rgba(201,168,76,0.4);
        }
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(201,168,76,0.3), transparent);
            margin: 30px 0;
        }
        .fallback {
            font-size: 13px;
            color: #6B7280;
            text-align: center;
            margin-top: 20px;
        }
        .fallback a {
            color: #C9A84C;
            text-decoration: none;
        }
        .expiry-note {
            font-size: 12px;
            color: #9CA3AF;
            text-align: center;
            margin-top: 25px;
            font-style: italic;
        }
        .email-footer {
            background-color: #F5F0E8;
            padding: 25px 30px;
            text-align: center;
        }
        .footer-text {
            font-size: 12px;
            color: #6B7280;
            margin-bottom: 8px;
        }
        .footer-brand {
            font-size: 14px;
            color: #0B1F3A;
            font-weight: 600;
            font-family: 'Georgia', 'Times New Roman', serif;
        }
        .footer-seal {
            font-size: 10px;
            color: #9CA3AF;
            margin-top: 12px;
            letter-spacing: 0.05em;
        }
        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 30px 20px;
            }
            .button {
                padding: 14px 30px;
                font-size: 13px;
            }
            .brand-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td>
                <div class="email-wrapper">
                    {{-- Header with Logo --}}
                    <div class="email-header">
                        <img src="{{ asset('images/lgulogo.png') }}" alt="LGU Daet" class="logo">
                        <h1 class="brand-title">Daet Listens</h1>
                        <p class="brand-subtitle">Official Transparency Portal</p>
                    </div>

                    {{-- Body --}}
                    <div class="email-body">
                        <p class="greeting">Hello {{ $user->first_name ?? 'there' }},</p>
                        
                        <p class="message">
                            We received a request to reset your password for your Daet Listens account. 
                            Click the button below to create a new password:
                        </p>

                        <div class="button-wrapper">
                            <a href="{{ $url }}" class="button">Reset My Password</a>
                        </div>

                        <p class="expiry-note">
                            This link will expire in 60 minutes for security reasons.
                        </p>

                        <div class="divider"></div>

                        <p class="fallback">
                            If the button doesn't work, copy and paste this link into your browser:<br>
                            <a href="{{ $url }}">{{ $url }}</a>
                        </p>

                        <p class="fallback" style="margin-top: 30px;">
                            If you didn't request this password reset, you can safely ignore this email. 
                            Your password will remain unchanged.
                        </p>
                    </div>

                    {{-- Footer --}}
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
