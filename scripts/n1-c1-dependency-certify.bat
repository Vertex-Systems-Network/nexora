@echo off
setlocal
cd /d "%~dp0\.."
php scripts\n1-c1-dependency-certify.php %*
exit /b %errorlevel%
