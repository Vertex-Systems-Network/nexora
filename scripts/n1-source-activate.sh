#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
BASE_URL="${1:-http://localhost}"
printf '[Nexora Source Activation] Verifying rc.94 / v5.29 / n1-v5.29 critical source set...\n'
php artisan nexora:source:activate --assert-current
printf '\nRestart/reload the active PHP/web service used by this Nexora deployment, then run:\n'
printf '  scripts/n1-source-web-ack.sh %s\n' "$BASE_URL"
