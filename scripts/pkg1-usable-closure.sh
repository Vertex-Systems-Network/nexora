#!/usr/bin/env sh
set -eu
cd "$(dirname "$0")/.."
exec php scripts/pkg1-usable-closure.php "$@"
