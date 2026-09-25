# Tailscale-Only Website Deployment

The Daet Listens website is served directly on the host's Tailscale interface. It is not bound to the LAN address.

## Current endpoint

- Tailscale IP: `100.88.237.96`
- MagicDNS URL: `http://leaf-1.tail05a1ab.ts.net:8000`

## Start

```bash
docker compose up -d pgsql redis mailpit minio
npm run build
APP_URL="http://leaf-1.tail05a1ab.ts.net:8000" \
  php artisan serve --host=100.88.237.96 --port=8000
```

The app should listen on `100.88.237.96:8000`, while PostgreSQL, Redis, Mailpit, and MinIO remain bound to `127.0.0.1`.

## Verify

```bash
ss -ltnp | grep 8000
curl http://100.88.237.96:8000/
curl http://leaf-1.tail05a1ab.ts.net:8000/
```

The LAN address `192.168.1.32:8000` should not accept connections.

## Optional HTTPS

Tailscale Serve requires a one-time operator permission:

```bash
sudo tailscale set --operator="$USER"
tailscale serve --bg --yes http://100.88.237.96:8000
```

After that, use the HTTPS MagicDNS URL shown by `tailscale serve status`.
