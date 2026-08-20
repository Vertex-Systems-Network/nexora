#!/usr/bin/env bash
set -euo pipefail
ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"
TOOLS_DIR="$ROOT_DIR/storage/app/nexora/tools"
mkdir -p "$TOOLS_DIR/composer-home" "$TOOLS_DIR/composer-cache" "$TOOLS_DIR/npm-cache" "$TOOLS_DIR/home"
if [[ -z "${COMPOSER_HOME:-}" && -z "${HOME:-}" ]]; then export COMPOSER_HOME="$TOOLS_DIR/composer-home"; fi
export COMPOSER_CACHE_DIR="${COMPOSER_CACHE_DIR:-$TOOLS_DIR/composer-cache}"
export NPM_CONFIG_CACHE="${NPM_CONFIG_CACHE:-$TOOLS_DIR/npm-cache}"
export HOME="${HOME:-$TOOLS_DIR/home}"
for command in php composer npm; do command -v "$command" >/dev/null || { echo "[ERROR] $command not found in PATH."; exit 1; }; done
[[ -f .env ]] || cp .env.example .env
php scripts/source-guard.php --source-only
[[ -f composer.lock ]] || { echo "[ERROR] composer.lock missing. Refresh and review dependency locks before bootstrap."; exit 1; }
[[ -f package-lock.json ]] || { echo "[ERROR] package-lock.json missing. Refresh and review dependency locks before bootstrap."; exit 1; }
composer install --no-interaction --prefer-dist --optimize-autoloader --no-progress
php artisan key:generate --force
npm ci --no-audit --no-fund
npm run build
php artisan optimize:clear
echo 'Source bootstrap complete. Open /install in your browser.'
