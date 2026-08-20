$ErrorActionPreference = "Stop"
& php "$PSScriptRoot/target-environment-bootstrap.php" --write @args
exit $LASTEXITCODE
