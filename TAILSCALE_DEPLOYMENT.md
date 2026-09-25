# Tailscale-Only Website Deployment

The Daet Listens website is served directly on the host's Tailscale interface. It is not bound to the LAN address.

## Current endpoint

- Tailscale IP: `100.88.237.96`
- MagicDNS URL: [http://leaf-1.tail05a1ab.ts.net:8000](http://leaf-1.tail05a1ab.ts.net:8000)
- Status: direct Tailscale binding is active and verified

> `tailscale serve status` may show `No serve config`. That is expected for this setup: Laravel is bound directly to the Tailscale IP, not proxied through Tailscale Serve.

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

## Port already in use

If the start command reports `Address already in use`, the detached Tailscale server is already running. Check it before starting another process:

```bash
ss -ltnp | grep 100.88.237.96:8000
curl http://leaf-1.tail05a1ab.ts.net:8000/
```

Only restart it when the existing listener should be replaced.

The LAN address `192.168.1.32:8000` should not accept connections.


## Mobile loading troubleshooting

If the MagicDNS URL stays on a loading screen, open the raw Tailscale IP first:

```text
http://100.88.237.96:8000
```

Make sure the phone's Tailscale app is connected to the same tailnet. The URL must use `http://`; the direct port is not HTTPS.

If the browser automatically expects HTTPS, configure Tailscale Serve:

```bash
sudo tailscale set --operator="$USER"
tailscale serve --bg --yes http://100.88.237.96:8000
tailscale serve status
```

Then open the HTTPS MagicDNS URL shown by `tailscale serve status`, without `:8000`.


Tailscale Serve requires a one-time operator permission:

```bash
sudo tailscale set --operator="$USER"
tailscale serve --bg --yes http://100.88.237.96:8000
```

After that, use the HTTPS MagicDNS URL shown by `tailscale serve status`.
