@echo off
setlocal EnableExtensions
cd /d "%~dp0\.."

set "NEXORA_TOOLS=%CD%\storage\app\nexora\tools"
if not exist "%NEXORA_TOOLS%\composer-home" mkdir "%NEXORA_TOOLS%\composer-home" >nul 2>&1
if not exist "%NEXORA_TOOLS%\composer-cache" mkdir "%NEXORA_TOOLS%\composer-cache" >nul 2>&1
if not exist "%NEXORA_TOOLS%\npm-cache" mkdir "%NEXORA_TOOLS%\npm-cache" >nul 2>&1
if not defined COMPOSER_HOME if not defined APPDATA set "COMPOSER_HOME=%NEXORA_TOOLS%\composer-home"
if not defined COMPOSER_CACHE_DIR set "COMPOSER_CACHE_DIR=%NEXORA_TOOLS%\composer-cache"
if not defined NPM_CONFIG_CACHE set "NPM_CONFIG_CACHE=%NEXORA_TOOLS%\npm-cache"
if not defined HOME if defined USERPROFILE set "HOME=%USERPROFILE%"
if not defined HOME set "HOME=%NEXORA_TOOLS%\home"
if not exist "%HOME%" mkdir "%HOME%" >nul 2>&1
if not defined APPDATA if defined USERPROFILE if exist "%USERPROFILE%\AppData\Roaming" set "APPDATA=%USERPROFILE%\AppData\Roaming"

echo ======================================================
echo Nexora Source Bootstrap - Windows / Laragon
echo This prepares Composer + frontend build for the UI wizard.
echo It does NOT run database migrations.
echo ======================================================

where php >nul 2>&1 || (echo [ERROR] PHP not found in PATH. & exit /b 1)
where composer >nul 2>&1 || (echo [ERROR] Composer not found in PATH. & exit /b 1)
where npm >nul 2>&1 || (echo [ERROR] npm not found in PATH. & exit /b 1)

if not exist .env copy .env.example .env >nul

php scripts\source-guard.php --source-only
if errorlevel 1 exit /b 1
if not exist composer.lock (echo [ERROR] composer.lock missing. Refresh and review dependency locks before bootstrap. & exit /b 1)
if not exist package-lock.json (echo [ERROR] package-lock.json missing. Refresh and review dependency locks before bootstrap. & exit /b 1)
composer install --no-interaction --prefer-dist --optimize-autoloader --no-progress
if errorlevel 1 exit /b 1
php artisan key:generate --force
if errorlevel 1 exit /b 1
call npm ci --no-audit --no-fund
if errorlevel 1 exit /b 1
call npm run build
if errorlevel 1 exit /b 1
php artisan optimize:clear
if errorlevel 1 exit /b 1

echo.
echo ======================================================
echo Source bootstrap complete.
echo Open: http://nexora.test/install
echo The browser wizard will configure MySQL and Nexora.
echo ======================================================
exit /b 0
