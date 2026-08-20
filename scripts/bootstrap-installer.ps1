$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $projectRoot

$toolsRoot = Join-Path $projectRoot 'storage/app/nexora/tools'
$composerHome = Join-Path $toolsRoot 'composer-home'
$composerCache = Join-Path $toolsRoot 'composer-cache'
$npmCache = Join-Path $toolsRoot 'npm-cache'
$privateHome = Join-Path $toolsRoot 'home'
foreach ($dir in @($composerHome,$composerCache,$npmCache,$privateHome)) { New-Item -ItemType Directory -Force -Path $dir | Out-Null }
if ([string]::IsNullOrWhiteSpace($env:COMPOSER_HOME) -and [string]::IsNullOrWhiteSpace($env:APPDATA)) { $env:COMPOSER_HOME = $composerHome }
if ([string]::IsNullOrWhiteSpace($env:COMPOSER_CACHE_DIR)) { $env:COMPOSER_CACHE_DIR = $composerCache }
if ([string]::IsNullOrWhiteSpace($env:NPM_CONFIG_CACHE)) { $env:NPM_CONFIG_CACHE = $npmCache }
if ([string]::IsNullOrWhiteSpace($env:HOME)) { $env:HOME = if (-not [string]::IsNullOrWhiteSpace($env:USERPROFILE)) { $env:USERPROFILE } else { $privateHome } }
if ([string]::IsNullOrWhiteSpace($env:APPDATA) -and -not [string]::IsNullOrWhiteSpace($env:USERPROFILE)) {
    $candidate = Join-Path $env:USERPROFILE 'AppData/Roaming'
    if (Test-Path $candidate) { $env:APPDATA = $candidate }
}
Write-Host 'Nexora Source Bootstrap - PowerShell'
foreach ($command in @('php','composer','npm')) { if (-not (Get-Command $command -ErrorAction SilentlyContinue)) { throw "$command not found in PATH." } }
if (-not (Test-Path '.env')) { Copy-Item '.env.example' '.env' }
php scripts/source-guard.php --source-only; if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
if (-not (Test-Path 'composer.lock')) { throw 'composer.lock missing. Refresh and review dependency locks before bootstrap.' }
if (-not (Test-Path 'package-lock.json')) { throw 'package-lock.json missing. Refresh and review dependency locks before bootstrap.' }
composer install --no-interaction --prefer-dist --optimize-autoloader --no-progress; if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
php artisan key:generate --force; if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
npm ci --no-audit --no-fund; if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
npm run build; if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
php artisan optimize:clear; if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
Write-Host 'Source bootstrap complete. Open: http://nexora.test/install'
