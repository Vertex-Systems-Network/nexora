$ErrorActionPreference = 'Stop'
Set-Location (Resolve-Path (Join-Path $PSScriptRoot '..'))
Write-Host 'Nexora TRUE ZERO browser installation test - PowerShell'
$confirm = Read-Host 'Type NEXORA to continue (removes DB nexora, dependencies/build/private tools, and local install state)'
if ($confirm -ne 'NEXORA') { Write-Host 'Cancelled.'; exit 1 }
if (-not (Get-Command php -ErrorAction SilentlyContinue)) { throw 'php not found in PATH.' }
$files = @('storage/app/nexora/installed.lock','storage/app/nexora/installing.lock','storage/app/nexora/deployment.lock','storage/app/nexora/deployment-access.key','storage/app/nexora/deployment-last-run.json','storage/app/nexora/deployment-last-interrupted.json','.env')
$dirs = @('storage/app/nexora/deployment-control','storage/app/nexora/installation-control','storage/app/nexora/database-backups','storage/app/nexora/release-stage','storage/app/nexora/environment','storage/app/nexora/tools','storage/app/nexora/target-runtime','storage/app/nexora/target-bootstrap','storage/app/nexora/target-intake','storage/app/nexora/dependency-intake','storage/app/nexora/target-remediation','storage/app/nexora/n1-c1','storage/app/nexora/n1-c2','storage/app/nexora/n1-c3','storage/app/nexora/n1-c4','storage/app/nexora/n1-c5','storage/app/nexora/n1-c6','storage/app/nexora/n1-target-execution','storage/app/nexora/upgrade','storage/app/nexora/update-trust','storage/app/nexora/runtime','bootstrap/cache/nexora','vendor','node_modules','public/build')
Remove-Item $files -Force -ErrorAction SilentlyContinue
Remove-Item $dirs -Recurse -Force -ErrorAction SilentlyContinue
php bootstrap/nexora-runtime-bootstrap.php; if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
php scripts/source-guard.php --source-only; if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
$env:DB_HOST='127.0.0.1'; $env:DB_PORT='3306'; $env:DB_DATABASE='nexora'; $env:DB_USERNAME='root'; $env:DB_PASSWORD='root'
php scripts/reset-zero-mysql.php; if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
php scripts/zero-state-verify.php --strict-source; if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
Write-Host 'Open https://nexora/ and complete deployment preparation + installer. Interrupt/retry once to verify interrupted-install recovery, then confirm /install locks out after completion.'
