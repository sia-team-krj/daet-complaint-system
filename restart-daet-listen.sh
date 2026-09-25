#!/usr/bin/env bash

set -Eeuo pipefail

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PUBLIC_DIR="$ROOT_DIR/public"
SERVER_SCRIPT="$ROOT_DIR/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"
LOG_FILE="${DAET_LISTEN_LOG_FILE:-$ROOT_DIR/storage/logs/daet-listen-server.log}"
PID_FILE="${DAET_LISTEN_PID_FILE:-/tmp/daet-listen-server.pid}"

BIND_HOST="${DAET_BIND_HOST:-}"
PORT="${DAET_PORT:-8000}"
PUBLIC_URL="${APP_URL:-https://leaf-1.tail05a1ab.ts.net}"
MINIO_BUCKET="${MINIO_BUCKET:-local}"
PHP_BIN="${PHP_BIN:-php}"
UPLOAD_MAX_FILESIZE="${DAET_UPLOAD_MAX_FILESIZE:-25M}"
POST_MAX_SIZE="${DAET_POST_MAX_SIZE:-30M}"
MAX_FILE_UPLOADS="${DAET_MAX_FILE_UPLOADS:-10}"

WITH_SERVICES=0
RUN_MIGRATIONS=0
RUN_BUILD=0

usage() {
    cat <<'EOF'
Usage: ./restart-daet-listen.sh [options]

Safely restarts the Laravel PHP server used by Tailscale Funnel.

Options:
  --with-services   Start and wait for PostgreSQL, Redis, Mailpit, and MinIO
  --migrate         Run php artisan migrate --force before starting
  --build           Run npm run build before starting
  --all             Run --with-services, --migrate, and --build
  -h, --help        Show this help

Environment overrides:
  DAET_BIND_HOST, DAET_PORT, DAET_UPLOAD_MAX_FILESIZE,
  DAET_POST_MAX_SIZE, DAET_MAX_FILE_UPLOADS, DAET_LISTEN_LOG_FILE,
  DAET_LISTEN_PID_FILE, PHP_BIN, APP_URL, MINIO_BUCKET
EOF
}

while (($# > 0)); do
    case "$1" in
        --with-services)
            WITH_SERVICES=1
            shift
            ;;
        --migrate)
            RUN_MIGRATIONS=1
            shift
            ;;
        --build)
            RUN_BUILD=1
            shift
            ;;
        --all)
            WITH_SERVICES=1
            RUN_MIGRATIONS=1
            RUN_BUILD=1
            shift
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            printf 'Unknown option: %s\n\n' "$1" >&2
            usage >&2
            exit 2
            ;;
    esac
done

if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
    printf 'PHP executable not found: %s\n' "$PHP_BIN" >&2
    exit 1
fi

if ! command -v lsof >/dev/null 2>&1; then
    printf 'lsof is required to safely find the existing listener.\n' >&2
    exit 1
fi

if [[ ! -f "$SERVER_SCRIPT" ]]; then
    printf 'Laravel server script not found: %s\n' "$SERVER_SCRIPT" >&2
    printf 'Run composer install before starting the server.\n' >&2
    exit 1
fi

if [[ -z "$BIND_HOST" || "$BIND_HOST" == "auto" ]]; then
    if command -v tailscale >/dev/null 2>&1; then
        BIND_HOST="$(tailscale ip -4 2>/dev/null | awk 'NF { print; exit }')"
    fi
fi

if [[ -z "$BIND_HOST" ]]; then
    printf 'Could not determine the Tailscale IPv4 address.\n' >&2
    printf 'Connect Tailscale or set DAET_BIND_HOST explicitly.\n' >&2
    exit 1
fi

cd "$ROOT_DIR"

if ((WITH_SERVICES)); then
    printf 'Starting support services...\n'
    docker compose up -d --wait --wait-timeout 60 pgsql redis mailpit minio
fi

if ((RUN_MIGRATIONS)); then
    printf 'Running database migrations...\n'
    "$PHP_BIN" artisan migrate --force
fi

if ((RUN_BUILD)); then
    printf 'Building production assets...\n'
    npm run build
fi

listener_pids() {
    lsof -nP -tiTCP:"$PORT" -sTCP:LISTEN 2>/dev/null || true
}

wait_for_bind_host() {
    if [[ "$BIND_HOST" == "0.0.0.0" || "$BIND_HOST" == "127.0.0.1" ]]; then
        return 0
    fi

    if ! command -v ip >/dev/null 2>&1; then
        printf 'The ip command is required to verify the Tailscale address.\n' >&2
        return 1
    fi

    for _ in {1..20}; do
        if ip -4 addr show dev tailscale0 2>/dev/null | grep -Fq " $BIND_HOST/"; then
            return 0
        fi
        sleep 0.5
    done

    printf 'Tailscale address %s is not available yet.\n' "$BIND_HOST" >&2
    return 1
}

is_our_server() {
    local pid="$1"
    local command_line

    [[ "$pid" =~ ^[0-9]+$ ]] || return 1
    kill -0 "$pid" 2>/dev/null || return 1
    command_line="$(tr '\0' ' ' < "/proc/$pid/cmdline" 2>/dev/null || true)"

    [[ "$command_line" == *"php"* && "$command_line" == *"server.php"* ]]
}

stop_pid() {
    local pid="$1"

    if ! is_our_server "$pid"; then
        printf 'Refusing to stop PID %s because it is not the Daet Listens PHP server.\n' "$pid" >&2
        return 1
    fi

    printf 'Stopping Daet Listens PHP server (PID %s)...\n' "$pid"
    kill "$pid" 2>/dev/null || true

    for _ in {1..20}; do
        if ! kill -0 "$pid" 2>/dev/null; then
            return 0
        fi
        sleep 0.25
    done

    printf 'PHP server did not stop cleanly; sending SIGKILL to PID %s.\n' "$pid" >&2
    kill -9 "$pid" 2>/dev/null || true
}

stop_requested=0
if [[ -f "$PID_FILE" ]]; then
    pid="$(cat "$PID_FILE" 2>/dev/null || true)"
    if [[ -n "$pid" ]] && kill -0 "$pid" 2>/dev/null; then
        stop_pid "$pid" || stop_requested=1
    else
        rm -f "$PID_FILE"
    fi
fi

while IFS= read -r pid; do
    [[ -n "$pid" ]] || continue
    if kill -0 "$pid" 2>/dev/null; then
        stop_pid "$pid" || stop_requested=1
    fi
done < <(listener_pids)

if ((stop_requested)); then
    printf 'A listener could not be stopped safely; nothing new was started.\n' >&2
    exit 1
fi

rm -f "$PID_FILE"
mkdir -p "$(dirname -- "$LOG_FILE")"

if ! wait_for_bind_host; then
    printf 'Nothing was started because the bind address is unavailable.\n' >&2
    exit 1
fi

printf 'Starting Laravel on %s:%s...\n' "$BIND_HOST" "$PORT"
start_failed=0
for attempt in 1 2 3; do
    printf '\n[%s] Starting Laravel (attempt %s/3)...\n' "$(date -Is)" "$attempt" >>"$LOG_FILE"
    (
        cd "$PUBLIC_DIR"
        nohup env \
            APP_URL="$PUBLIC_URL" \
            MINIO_BUCKET="$MINIO_BUCKET" \
            "$PHP_BIN" \
            -d upload_max_filesize="$UPLOAD_MAX_FILESIZE" \
            -d post_max_size="$POST_MAX_SIZE" \
            -d max_file_uploads="$MAX_FILE_UPLOADS" \
            -S "$BIND_HOST:$PORT" \
            "$SERVER_SCRIPT" \
            >>"$LOG_FILE" 2>&1 &
        printf '%s\n' "$!" > "$PID_FILE"
    )

    pid="$(cat "$PID_FILE")"
    for _ in {1..10}; do
        if kill -0 "$pid" 2>/dev/null && listener_pids | grep -Fxq "$pid"; then
            start_failed=0
            break 2
        fi
        sleep 0.5
    done

    start_failed=1
    rm -f "$PID_FILE"
    sleep 1
done

if ((start_failed)); then
    printf 'Laravel failed to start. Recent log output:\n' >&2
    tail -n 30 "$LOG_FILE" >&2 || true
    exit 1
fi

printf 'Daet Listens restarted successfully (PID %s).\n' "$pid"
printf 'Log: %s\n' "$LOG_FILE"
printf 'Public URL: %s\n' "$PUBLIC_URL"
