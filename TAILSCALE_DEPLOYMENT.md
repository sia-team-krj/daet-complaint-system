# Tailscale-Only Website Deployment

The Daet Listens website is available only to authorized devices on the Tailscale tailnet. The Laravel server binds to the host's Tailscale interface, while support services remain on localhost.

## Current endpoint

- Private HTTPS URL: [https://leaf-1.tail05a1ab.ts.net](https://leaf-1.tail05a1ab.ts.net)
- Tailscale IP: `100.88.237.96`
- Direct backend URL: `http://100.88.237.96:8000`
- LAN address: `192.168.1.32:8000` (not exposed)

## Start

```bash
docker compose up -d pgsql redis mailpit minio
npm run build
APP_URL="https://leaf-1.tail05a1ab.ts.net" \
  php artisan serve --host=100.88.237.96 --port=8000
```

Tailscale Serve proxies the private HTTPS URL to the local Tailscale-bound Laravel server.

## Verify

```bash
ss -ltnp | grep 100.88.237.96:8000
curl http://100.88.237.96:8000/
curl https://leaf-1.tail05a1ab.ts.net/
tailscale serve status
```

The LAN address should not accept connections.

## Port already in use

If the start command reports `Address already in use`, the detached Tailscale server is already running. Check it before starting another process:

```bash
ss -ltnp | grep 100.88.237.96:8000
curl https://leaf-1.tail05a1ab.ts.net/
```

Only restart it when the existing listener should be replaced.

## Mobile loading troubleshooting

Make sure the phone's Tailscale app is connected to the same tailnet. Use the HTTPS MagicDNS URL first. If MagicDNS does not resolve, use the direct Tailscale IP fallback:

```text
http://100.88.237.96:8000
```

Tailscale Serve requires a one-time operator permission on the host:

```bash
sudo tailscale set --operator="$USER"
tailscale serve --bg --yes http://100.88.237.96:8000
tailscale serve status
```

The `tailscale serve status` output should identify the HTTPS endpoint as tailnet-only.
