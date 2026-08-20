@echo off
setlocal EnableExtensions
cd /d "%~dp0\.."

echo ======================================================
echo Nexora TRUE ZERO browser installation test - Windows / Laragon
echo MySQL: 127.0.0.1:3306 / nexora / root
echo WARNING: database "nexora", dependencies/build, private bootstrap tools,
echo and all local installer/deployment state will be removed.
echo ======================================================
set /p "CONFIRM=Type NEXORA to continue: "
if /I not "%CONFIRM%"=="NEXORA" (echo Cancelled. & exit /b 1)

where php >nul 2>&1 || (echo [ERROR] PHP not found in PATH. & exit /b 1)

for %%F in (
  "storage\app\nexora\installed.lock"
  "storage\app\nexora\installing.lock"
  "storage\app\nexora\deployment.lock"
  "storage\app\nexora\deployment-access.key"
  "storage\app\nexora\deployment-last-run.json"
  "storage\app\nexora\deployment-last-interrupted.json"
  ".env"
) do if exist %%F del /f /q %%F
for %%D in (
  "storage\app\nexora\deployment-control"
  "storage\app\nexora\installation-control"
  "storage\app\nexora\database-backups"
  "storage\app\nexora\release-stage"
  "storage\app\nexora\environment"
  "storage\app\nexora\tools"
  "storage\app\nexora\target-runtime"
  "storage\app\nexora\target-bootstrap"
  "storage\app\nexora\target-intake"
  "storage\app\nexora\target-remediation"
  "storage\app\nexora\n1-c1"
  "storage\app\nexora\n1-c2"
  "storage\app\nexora\n1-c3"
  "storage\app\nexora\n1-c4"
  "storage\app\nexora\n1-c5"
  "storage\app\nexora\n1-c6"
  "storage\app\nexora\n1-target-execution"
  "storage\app\nexora\update-trust"
  "storage\app\nexora\dependency-intake"
  "storage\app\nexora\upgrade"
  "bootstrap\cache\nexora"
  "vendor"
  "node_modules"
  "public\build"
) do if exist %%D rmdir /s /q %%D

php bootstrap\nexora-runtime-bootstrap.php
if errorlevel 1 exit /b 1
php scripts\source-guard.php --source-only
if errorlevel 1 exit /b 1

set "DB_HOST=127.0.0.1"
set "DB_PORT=3306"
set "DB_DATABASE=nexora"
set "DB_USERNAME=root"
set "DB_PASSWORD=root"
php scripts\reset-zero-mysql.php
if errorlevel 1 exit /b 1
php scripts\zero-state-verify.php --strict-source
if errorlevel 1 exit /b 1

echo.
echo ======================================================
echo TRUE ZERO state is ready for browser-only installation.
echo 1. Open https://nexora/
echo 2. Nexora must render Deployment Preparation at the canonical URL.
echo 3. Prepare dependencies/build or upload a certified production release.
echo 4. Continue to /install and complete the main wizard.
echo 5. Interrupt/retry once when testing interrupted-install recovery behavior.
echo 6. After installation, /install must lock out and login must be reachable.
echo 7. Run scripts\quality-check.bat for developer QA.
echo ======================================================
exit /b 0
