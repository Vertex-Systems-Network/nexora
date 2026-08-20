$ErrorActionPreference = 'Stop'
Set-Location (Resolve-Path (Join-Path $PSScriptRoot '..'))
if (-not (Get-Command php -ErrorAction SilentlyContinue)) { throw 'php was not found in PATH.' }
& php scripts/certify-release.php @args
exit $LASTEXITCODE
