$ErrorActionPreference = 'Stop'
& php (Join-Path $PSScriptRoot 'pkg1-status.php') @args
exit $LASTEXITCODE
