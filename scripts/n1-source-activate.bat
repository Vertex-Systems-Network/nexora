@echo off
setlocal
cd /d "%~dp0.."

echo [Nexora Source Activation] Clearing Laravel caches and verifying the CLI critical source set...
php artisan nexora:source:activate --assert-current
if errorlevel 1 (
  echo.
  echo [Nexora Source Activation] FAIL - CLI is not executing the packaged rc.94 / v5.29 / n1-v5.29 critical source set.
  exit /b 1
)

echo.
echo [Nexora Source Activation] CLI source set is current and a fresh web-ack nonce was issued.
echo 1. Restart/reload the Laragon Apache/Nginx/PHP service.
echo 2. Run: scripts\n1-source-web-ack.bat http://nexora
echo 3. Confirm /install shows: 1.0.0-rc.94 / v5.29 / n1-v5.29 and critical source 37/37 and runtime classes 34/34.
exit /b 0
