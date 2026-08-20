@echo off
setlocal
cd /d "%~dp0\.."
php "%~dp0dependency-lock-promotion-recover.php" %*
exit /b %errorlevel%
