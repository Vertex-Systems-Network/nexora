#!/usr/bin/env sh
exec php "$(dirname "$0")/target-prerequisite-restart-verify.php" "$@"
