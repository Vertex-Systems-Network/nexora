@echo off
setlocal EnableExtensions
cd /d "%~dp0\.."
where php >nul 2>&1 || (echo [ERROR] PHP not found in PATH. & exit /b 1)
php scripts\build-production-release.php
exit /b %ERRORLEVEL%
