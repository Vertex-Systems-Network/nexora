#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
base_url="${1:-http://nexora}"
base_url="${base_url%/install/source-status}"
base_url="${base_url%/install}"

if php artisan nexora:source:status --require-web-ack >/dev/null 2>&1; then
  printf '[Nexora Source Web Ack] PASS - current web process already acknowledged this activation generation.\n'
  exit 0
fi

token="$(php artisan nexora:source:status --web-token 2>/dev/null || true)"
if [[ ! "$token" =~ ^[a-f0-9]{64}$ ]]; then
  printf '[Nexora Source Web Ack] FAIL - no current one-time acknowledgement token is available.\n' >&2
  printf 'Run scripts/n1-source-activate.sh first, then restart/reload PHP/web.\n' >&2
  exit 1
fi

printf '[Nexora Source Web Ack] Securely acknowledging %s/install/source-status ...\n' "$base_url"
curl --fail --silent --show-error \
  -H 'Accept: application/json' \
  -H "X-Nexora-Activation-Token: $token" \
  "$base_url/install/source-status"
printf '\n[Nexora Source Web Ack] Verifying CLI/web disk source + loaded runtime classes + activation nonce...\n'
php artisan nexora:source:status --require-web-ack
