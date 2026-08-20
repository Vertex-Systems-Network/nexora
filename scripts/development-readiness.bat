@echo off
setlocal
php "%~dp0development-readiness.php" %*
exit /b %ERRORLEVEL%
