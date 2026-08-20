#!/usr/bin/env sh
set -eu
exec php "$(dirname "$0")/development-readiness.php" "$@"
