#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
command -v php >/dev/null || { echo '[ERROR] php was not found in PATH.' >&2; exit 1; }
exec php scripts/certify-release.php "$@"
