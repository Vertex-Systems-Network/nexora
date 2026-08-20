@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0.."

set "BASE_URL=%~1"
if "%BASE_URL%"=="" set "BASE_URL=http://nexora"
set "BASE_URL=%BASE_URL:/install/source-status=%"
set "BASE_URL=%BASE_URL:/install=%"

php artisan nexora:source:status --require-web-ack >nul 2>&1
if not errorlevel 1 (
  echo [Nexora Source Web Ack] PASS - current web process already acknowledged this activation generation.
  exit /b 0
)

set "ACK_TOKEN="
for /f "usebackq delims=" %%T in (`php artisan nexora:source:status --web-token 2^>nul`) do (
  set "ACK_TOKEN=%%T"
)

if "!ACK_TOKEN!"=="" (
  echo [Nexora Source Web Ack] FAIL - no current one-time acknowledgement token is available.
  echo Run scripts\n1-source-activate.bat first, then restart/reload Laragon PHP/web.
  exit /b 1
)

echo [Nexora Source Web Ack] Securely acknowledging %BASE_URL%/install/source-status ...
curl.exe --fail --silent --show-error --no-cache ^
  -H "Accept: application/json" ^
  -H "X-Nexora-Activation-Token: !ACK_TOKEN!" ^
  "%BASE_URL%/install/source-status"
if errorlevel 1 (
  echo.
  echo [Nexora Source Web Ack] FAIL - secure web source acknowledgement was rejected.
  echo Confirm Laragon was restarted and the URL points at this exact Nexora installation.
  exit /b 1
)

echo.
echo [Nexora Source Web Ack] Verifying CLI/web disk source + loaded runtime classes + activation nonce...
php artisan nexora:source:status --require-web-ack
if errorlevel 1 (
  echo.
  echo [Nexora Source Web Ack] FAIL - CLI and web runtime generations do not converge.
  echo Restart/reload Laragon Apache/Nginx/PHP and repeat activation.
  exit /b 1
)

echo.
echo [Nexora Source Web Ack] PASS - CLI and web PHP acknowledge the exact disk source set and loaded runtime class generation.
exit /b 0
