$ErrorActionPreference = 'Stop'
Set-Location (Resolve-Path (Join-Path $PSScriptRoot '..'))
php scripts/build-production-release.php
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
