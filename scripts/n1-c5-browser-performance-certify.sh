#!/usr/bin/env sh
set -eu
SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
exec php "$SCRIPT_DIR/n1-c5-browser-performance-certify.php" "$@"
