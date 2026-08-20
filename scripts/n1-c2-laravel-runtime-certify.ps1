$ErrorActionPreference='Stop'
$root=(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $root
& php scripts/n1-c2-laravel-runtime-certify.php @args
exit $LASTEXITCODE
