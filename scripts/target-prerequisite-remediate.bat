@echo off
setlocal
php "%~dp0target-prerequisite-remediate.php" %*
exit /b %ERRORLEVEL%
