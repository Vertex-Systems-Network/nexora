@echo off
php "%~dp0trusted-update-cleanup.php" %*
exit /b %ERRORLEVEL%
