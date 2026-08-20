@echo off
php "%~dp0release-trust-anchor.php" %*
exit /b %ERRORLEVEL%
