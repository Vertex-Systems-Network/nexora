@echo off
setlocal
cd /d "%~dp0\.."
php "%~dp0dependency-lock-promote.php" %*
exit /b %errorlevel%
