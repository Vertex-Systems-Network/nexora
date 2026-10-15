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
php scripts/bootstrap-origin-doctor.php; if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
php scripts/source-guard.php --source-only; if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
if (-not (Test-Path 'composer.lock')) { throw 'composer.lock missing. Refresh and review dependency locks before bootstrap.' }
if (-not (Test-Path 'package-lock.json')) { throw 'package-lock.json missing. Refresh and review dependency locks before bootstrap.' }
composer install --no-interaction --prefer-dist --optimize-autoloader --no-progress; if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
$envContent = Get-Content '.env' -Raw
# Preserve the current application encryption key on repeat bootstraps.
# Rotating an installed key requires the separate authorized recovery workflow.
if ($envContent -notmatch '(?m)^APP_KEY=.{8,}\r?$') {
    php artisan key:generate
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
}
npm ci --no-audit --no-fund; if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
npm run build; if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
php artisan optimize:clear; if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
Write-Host 'Source bootstrap complete. Open the configured APP_URL followed by /install.'
