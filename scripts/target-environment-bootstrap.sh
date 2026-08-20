#!/usr/bin/env sh
set -eu
php "$(dirname "$0")/target-environment-bootstrap.php" --write "$@"
