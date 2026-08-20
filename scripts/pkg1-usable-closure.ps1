$ErrorActionPreference = 'Stop'
Set-Location (Resolve-Path (Join-Path $PSScriptRoot '..'))
& php 'scripts/pkg1-usable-closure.php' @args
exit $LASTEXITCODE
