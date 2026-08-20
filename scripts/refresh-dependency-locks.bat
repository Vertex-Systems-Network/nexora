@echo off
setlocal
cd /d "%~dp0\.."
php "%~dp0dependency-lock-refresh.php" %*
exit /b %errorlevel%
