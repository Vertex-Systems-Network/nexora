$ErrorActionPreference='Stop'
Set-Location (Join-Path $PSScriptRoot '..')
& php scripts/dependency-lock-review.php @args
exit $LASTEXITCODE
