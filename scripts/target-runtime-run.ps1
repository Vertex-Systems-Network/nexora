$ErrorActionPreference = 'Stop'
Set-Location (Resolve-Path (Join-Path $PSScriptRoot '..'))
& php scripts/target-runtime-run.php @args
exit $LASTEXITCODE
