@echo off
setlocal
php "%~dp0target-evidence-intake.php" %*
exit /b %ERRORLEVEL%
