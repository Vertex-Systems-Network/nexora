@echo off
setlocal EnableExtensions
cd /d "%~dp0\.."

where php >nul 2>&1
if errorlevel 1 (echo [ERROR] PHP was not found in PATH. & exit /b 1)

rem N1.0: one platform-neutral certification runner owns the actual gates.
rem Override the isolated database with NEXORA_CERT_DB_* variables when needed.
php scripts\certify-release.php %*
exit /b %ERRORLEVEL%
