@echo off
setlocal
php "%~dp0n1-c4-operations-certify.php" %*
exit /b %ERRORLEVEL%
