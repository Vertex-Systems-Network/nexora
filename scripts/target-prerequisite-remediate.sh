#!/usr/bin/env sh
set -eu
php "$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)/target-prerequisite-remediate.php" "$@"
