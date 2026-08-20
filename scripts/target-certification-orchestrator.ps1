$ErrorActionPreference = 'Stop'
Set-Location (Resolve-Path (Join-Path $PSScriptRoot '..'))
& php scripts/target-certification-orchestrator.php @args
exit $LASTEXITCODE
