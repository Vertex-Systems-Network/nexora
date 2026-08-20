$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..')
& php scripts/n1-c3-database-matrix-certify.php @args
exit $LASTEXITCODE
