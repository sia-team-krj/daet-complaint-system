# Tailscale Funnel Website Deployment

The Daet Listens website is publicly available through Tailscale Funnel. The Laravel server binds to the host's Tailscale interface, and Funnel proxies the public HTTPS URL to it.

## Public endpoint

- Public HTTPS URL: [https://leaf-1.tail05a1ab.ts.net](https://leaf-1.tail05a1ab.ts.net)
- Tailscale backend IP: `100.88.237.96`
- Direct backend URL: `http://100.88.237.96:8000`

Anyone with the public URL can view public pages. Daet Listens authentication is still required for resident, staff, and admin areas.

## Start the backend

```bash
docker compose up -d pgsql redis mailpit minio
npm run build
APP_URL="https://leaf-1.tail05a1ab.ts.net" \
  php artisan serve --host=100.88.237.96 --port=8000
```

The Laravel server listens on the Tailscale IP. PostgreSQL, Redis, Mailpit, and MinIO remain bound to localhost.

## Enable or verify Funnel

```bash
tailscale funnel --bg --yes http://100.88.237.96:8000
tailscale funnel status
```

The status should report:

```text
https://leaf-1.tail05a1ab.ts.net (Funnel on)
```

## Verify

```bash
curl https://leaf-1.tail05a1ab.ts.net/
ss -ltnp | grep 100.88.237.96:8000
```

The public URL should return the application without requiring a Tailscale login on the visitor's device.

## Disable public access

To turn off public exposure and return to Tailscale Serve/private access:

```bash
tailscale funnel --https=443 off
tailscale serve --bg --yes http://100.88.237.96:8000
```

Review the current exposure with:

```bash
tailscale serve status
tailscale funnel status
```

> Funnel exposes the public website to the internet. Do not enable it on an environment containing sensitive data or demo credentials without reviewing the deployment.
