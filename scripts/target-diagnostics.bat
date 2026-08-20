@echo off
setlocal EnableExtensions
cd /d "%~dp0\.."
where php >nul 2>&1
if errorlevel 1 (echo [ERROR] PHP was not found in PATH. & exit /b 1)
php scripts\target-diagnostics.php %*
exit /b %ERRORLEVEL%
