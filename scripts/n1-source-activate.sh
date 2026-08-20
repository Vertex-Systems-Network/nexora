#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
printf '[Nexora Source Activation] Verifying rc.94 / v5.29 / n1-v5.29 critical source set...\n'
php artisan nexora:source:activate --assert-current
printf '\nRestart/reload the web PHP process, then run:\n'
printf '  scripts/n1-source-web-ack.sh http://nexora\n'
