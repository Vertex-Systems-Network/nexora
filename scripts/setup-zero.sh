#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
echo 'Nexora TRUE ZERO browser installation test'
read -r -p 'Type NEXORA to continue (removes DB nexora, dependencies/build/private tools, and local install state): ' CONFIRM
[[ "$CONFIRM" == 'NEXORA' ]] || { echo 'Cancelled.'; exit 1; }
command -v php >/dev/null || { echo '[ERROR] php not found.'; exit 1; }
rm -f storage/app/nexora/installed.lock storage/app/nexora/installing.lock storage/app/nexora/deployment.lock storage/app/nexora/deployment-access.key storage/app/nexora/deployment-last-run.json storage/app/nexora/deployment-last-interrupted.json .env
rm -rf storage/app/nexora/deployment-control storage/app/nexora/installation-control storage/app/nexora/database-backups storage/app/nexora/release-stage storage/app/nexora/environment storage/app/nexora/tools storage/app/nexora/target-runtime storage/app/nexora/target-bootstrap storage/app/nexora/target-intake storage/app/nexora/dependency-intake storage/app/nexora/target-remediation storage/app/nexora/n1-c1 storage/app/nexora/n1-c2 storage/app/nexora/n1-c3 storage/app/nexora/n1-c4 storage/app/nexora/n1-c5 storage/app/nexora/n1-c6 storage/app/nexora/n1-target-execution storage/app/nexora/upgrade storage/app/nexora/update-trust storage/app/nexora/runtime bootstrap/cache/nexora vendor node_modules public/build
php bootstrap/nexora-runtime-bootstrap.php
php scripts/source-guard.php --source-only
DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=nexora DB_USERNAME=root DB_PASSWORD=root php scripts/reset-zero-mysql.php
php scripts/zero-state-verify.php --strict-source
echo 'Open https://nexora/ and complete deployment preparation + installer. Interrupt/retry once to verify interrupted-install recovery, then confirm /install locks out after completion.'
