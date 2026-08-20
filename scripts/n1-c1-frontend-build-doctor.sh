#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
exec php scripts/n1-c1-frontend-build-doctor.php "$@"
