$ErrorActionPreference = 'Stop'
& php (Join-Path $PSScriptRoot 'target-evidence-intake.php') @args
exit $LASTEXITCODE
