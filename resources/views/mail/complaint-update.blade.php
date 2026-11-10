<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #0b1f3a;
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
            background: linear-gradient(135deg, #0b1f3a 0%, #12294d 100%);
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
            color: #c9a84c;
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
            color: #0b1f3a;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .ticket-ref {
            font-size: 12px;
            color: #9ca3af;
            letter-spacing: 0.05em;
            margin-bottom: 24px;
        }
        .headline {
            font-size: 17px;
            color: #0b1f3a;
            font-weight: 700;
            line-height: 1.4;
            margin-bottom: 16px;
        }
        .detail-box {
            background-color: #f5f0e8;
            border-left: 4px solid #c9a84c;
            border-radius: 0 4px 4px 0;
            padding: 16px 20px;
            font-size: 15px;
            color: #0b1f3a;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .message {
            font-size: 15px;
            color: #4b5563;
            line-height: 1.7;
            margin-bottom: 24px;
        }
        .note-block {
            background-color: #ffffff;
            border: 1px solid rgba(201,168,76,0.35);
            border-radius: 4px;
            padding: 18px 20px;
            margin-bottom: 24px;
        }
        .note-label {
            font-size: 11px;
            color: #9ca3af;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .note-body {
            font-size: 15px;
            color: #4b5563;
            line-height: 1.7;
            white-space: pre-line;
        }
        .button-wrapper {
            text-align: center;
            margin: 35px 0;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #c9a84c, #e2c06a);
            color: #0b1f3a;
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
            color: #6b7280;
            text-align: center;
            margin-top: 20px;
        }
        .fallback a {
            color: #c9a84c;
            text-decoration: none;
        }
        .email-footer {
            background-color: #f5f0e8;
            padding: 25px 30px;
            text-align: center;
        }
        .footer-text {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        .footer-brand {
            font-size: 14px;
            color: #0b1f3a;
            font-weight: 600;
            font-family: 'Georgia', 'Times New Roman', serif;
        }
        .footer-seal {
            font-size: 10px;
            color: #9ca3af;
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
                        <img src="{{ asset('images/lgulogo.webp') }}" alt="LGU Daet" class="logo">
                        <h1 class="brand-title">Daet Listens</h1>
                        <p class="brand-subtitle">Official Transparency Portal</p>
                    </div>

                    {{-- Body --}}
                    <div class="email-body">
                        <p class="greeting">{{ __('mail.ticket_greeting', ['name' => $firstName]) }}</p>
                        <p class="ticket-ref">{{ __('mail.ticket_reference', ['ticket' => $ticketId]) }}</p>

                        <div class="headline">{{ $headline }}</div>

                        <div class="detail-box">{{ $summary }}</div>

                        @if($note)
                            <div class="note-block">
                                <div class="note-label">{{ $noteLabel }}</div>
                                <div class="note-body">{{ $note }}</div>
                            </div>
                        @endif

                        <div class="button-wrapper">
                            <a href="{{ $url }}" class="button">{{ __('mail.ticket_cta') }}</a>
                        </div>

                        <div class="divider"></div>

                        <p class="fallback">
                            {{ __('mail.ticket_ignore_note') }}
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
