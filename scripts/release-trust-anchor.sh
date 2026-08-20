#!/usr/bin/env sh
exec php "$(dirname "$0")/release-trust-anchor.php" "$@"
