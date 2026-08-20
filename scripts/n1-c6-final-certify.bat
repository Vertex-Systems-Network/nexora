@echo off
setlocal
php "%~dp0n1-c6-final-certify.php" %*
exit /b %errorlevel%
