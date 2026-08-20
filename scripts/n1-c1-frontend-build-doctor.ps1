$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..')
& php 'scripts/n1-c1-frontend-build-doctor.php' @args
exit $LASTEXITCODE
