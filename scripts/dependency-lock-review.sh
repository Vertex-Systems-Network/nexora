#!/usr/bin/env sh
set -eu
cd "$(dirname "$0")/.."
exec php scripts/dependency-lock-review.php "$@"
