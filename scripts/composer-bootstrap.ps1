$ErrorActionPreference = 'Stop'
& php (Join-Path $PSScriptRoot 'composer-bootstrap.php') @args
exit $LASTEXITCODE
