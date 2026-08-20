@echo off
setlocal
cd /d "%~dp0\.."
php scripts\dependency-lock-review.php %*
exit /b %errorlevel%
