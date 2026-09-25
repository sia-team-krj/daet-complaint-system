# Tailscale Funnel Website Deployment

The Daet Listens website is publicly available through Tailscale Funnel. The Laravel server binds to the host's Tailscale interface, and Funnel proxies the public HTTPS URL to it.

## Public endpoint

- Public HTTPS URL: [https://leaf-1.tail05a1ab.ts.net](https://leaf-1.tail05a1ab.ts.net)
- Tailscale backend IP: `100.88.237.96`
- Direct backend URL: `http://100.88.237.96:8000`

Anyone with the public URL can view public pages. Daet Listens authentication is still required for resident, staff, and admin areas.

## Start the backend

Install the locked PHP dependencies before starting Laravel. This host does not have the optional `iconv` extension, so use the platform-requirement override here:

```bash
composer install --no-interaction --prefer-dist --ignore-platform-req=ext-iconv
```

Start the support services and create the configured MinIO bucket once:

```bash
docker compose up -d pgsql redis mailpit minio
docker compose exec -T minio sh -c 'mc alias set local http://127.0.0.1:9000 "${MINIO_ROOT_USER:-admin}" "${MINIO_ROOT_PASSWORD:-password}" >/dev/null && mc mb --ignore-existing local/local'
```

Apply migrations and build the production assets:

```bash
php artisan migrate --force
npm run build
```

The public filing form accepts up to five photos, each up to 5 MB. The PHP built-in server must therefore be started with a larger request limit than the host defaults. Run it from `public/` so Laravel's front controller and the Tailscale binding are both correct:

```bash
cd public
APP_URL="https://leaf-1.tail05a1ab.ts.net" \
MINIO_BUCKET=local \
php -d upload_max_filesize=25M \
    -d post_max_size=30M \
    -d max_file_uploads=10 \
    -S 100.88.237.96:8000 \
    ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
```

Do not run Composer/Vite development mode at the same time as this server; both use port `8000`. PostgreSQL, Redis, Mailpit, and MinIO remain bound to localhost.

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
