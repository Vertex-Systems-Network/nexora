$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..')
& php scripts/target-diagnostics.php @args
exit $LASTEXITCODE
