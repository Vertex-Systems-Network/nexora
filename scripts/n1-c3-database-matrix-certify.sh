#!/usr/bin/env sh
set -eu
cd "$(dirname "$0")/.."
exec php scripts/n1-c3-database-matrix-certify.php "$@"
