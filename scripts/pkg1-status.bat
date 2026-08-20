@echo off
setlocal
php "%~dp0pkg1-status.php" %*
exit /b %ERRORLEVEL%
