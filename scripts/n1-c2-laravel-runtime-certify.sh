#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
exec php scripts/n1-c2-laravel-runtime-certify.php "$@"
