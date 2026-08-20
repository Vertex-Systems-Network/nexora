$ErrorActionPreference='Stop'
Set-Location (Join-Path $PSScriptRoot '..')
& php scripts/target-prerequisite-intake.php @args
exit $LASTEXITCODE
