#!/usr/bin/env bash

set -Eeuo pipefail

if [[ "${1:-}" == "-h" || "${1:-}" == "--help" ]]; then
    cat <<'EOF'
Usage: ./keep-device-awake.sh

Temporarily prevents idle sleep, suspend, and lid-close sleep while the command runs.
Press Ctrl+C to release the inhibitor.
EOF
    exit 0
fi

if ! command -v systemd-inhibit >/dev/null 2>&1; then
    printf 'systemd-inhibit is required but was not found.\n' >&2
    exit 1
fi

printf 'Keeping this device awake. Press Ctrl+C to stop.\n'
exec systemd-inhibit \
    --what=handle-lid-switch:sleep:idle \
    --who='Daet Listens server' \
    --why='Keep the Daet Listens server available' \
    --mode=block \
    sleep infinity
