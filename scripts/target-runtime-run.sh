#!/usr/bin/env sh
set -eu
cd "$(dirname "$0")/.."
exec php scripts/target-runtime-run.php "$@"
