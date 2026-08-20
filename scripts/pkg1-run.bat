@echo off
setlocal
if "%~1"=="" (
  echo Usage: scripts\pkg1-run.bat "REAL NAME" [base-url]
  exit /b 2
)
set "NX_BASE=%~2"
if "%NX_BASE%"=="" set "NX_BASE=http://nexora"
php "%~dp0pkg1-run.php" --operator="%~1" --base-url="%NX_BASE%"
exit /b %ERRORLEVEL%
