@echo off
setlocal
cd /d "%~dp0\.."
php scripts\n1-c2-laravel-runtime-certify.php %*
exit /b %errorlevel%
