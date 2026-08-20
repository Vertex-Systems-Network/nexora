@echo off
setlocal
php "%~dp0n1-c5-browser-performance-certify.php" %*
exit /b %errorlevel%
