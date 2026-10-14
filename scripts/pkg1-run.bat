@echo off
setlocal
if "%~1"=="" (
  echo Usage: scripts\pkg1-run.bat "REAL NAME" [base-url]
  exit /b 2
)
set "NX_BASE=%~2"
if "%NX_BASE%"=="" if defined APP_URL set "NX_BASE=%APP_URL%"
if "%NX_BASE%"=="" (
  echo ERROR: Provide the application origin as the second argument or set APP_URL.
  exit /b 2
)
php "%~dp0pkg1-run.php" --operator="%~1" --base-url="%NX_BASE%"
exit /b %ERRORLEVEL%
