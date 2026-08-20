$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..')
& php scripts/final-target-run.php @args
exit $LASTEXITCODE
