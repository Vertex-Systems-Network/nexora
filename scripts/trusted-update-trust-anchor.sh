#!/usr/bin/env sh
set -eu
php "$(dirname "$0")/trusted-update-trust-anchor.php" "$@"
