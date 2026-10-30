# **Daet Public Service Complaint & Transparency Management System**

A specialized, geo-spatial platform designed for the **Local Government Unit (LGU) of Daet, Camarines Norte**. This system serves as a transparent bridge between citizens and local authorities, focusing on the efficient reporting and resolution of **public service utilities and infrastructure issues**.

## Public deployment

The website is publicly available through Tailscale Funnel at:

**[Open Daet Listens](https://leaf-1.tail05a1ab.ts.net)**

Visitors do not need a Tailscale account to view public pages. Resident, staff, and admin areas require a Daet Listens login and a verified email address. The LAN address remains blocked. See the full [Tailscale Funnel deployment guide](TAILSCALE_DEPLOYMENT.md) for startup, verification, and disabling public access.

## Email verification and password recovery

New accounts must verify their email address with a six-digit OTP before accessing the application. Password reset links use the same Brevo delivery path when it is configured, with a generic response that does not reveal whether an address belongs to an account. The local development setup uses Mailpit:

- Mailpit inbox: `http://localhost:8025`
- Mailpit SMTP: `127.0.0.1:1025`

For production, set `BREVO_API_KEY` and a verified `BREVO_FROM_EMAIL` in the environment. Brevo's transactional API is used for OTP and password reset delivery when the key is present, with the configured Laravel mailer as the fallback. If Brevo reports an unauthorized IP, add the production server's public IP under Brevo's authorized-IP settings. Run `php artisan migrate --force` during deployment. OTP expiry, resend cooldown, and maximum attempts are configurable through the `EMAIL_OTP_*` variables in `.env.example`.
