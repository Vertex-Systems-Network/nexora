#!/usr/bin/env sh
exec php "$(dirname "$0")/release-signing-key.php" "$@"
