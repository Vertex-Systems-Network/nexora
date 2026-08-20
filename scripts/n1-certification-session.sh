#!/usr/bin/env sh
exec php "$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)/n1-certification-session.php" "$@"
