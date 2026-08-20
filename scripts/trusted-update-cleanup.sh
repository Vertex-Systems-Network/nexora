#!/usr/bin/env sh
set -eu
php "$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)/trusted-update-cleanup.php" "$@"
