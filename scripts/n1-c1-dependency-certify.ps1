$ErrorActionPreference='Stop'
$root=(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $root
& php scripts/n1-c1-dependency-certify.php @args
exit $LASTEXITCODE
