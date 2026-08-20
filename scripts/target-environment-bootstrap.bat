@echo off
setlocal
php "%~dp0target-environment-bootstrap.php" --write %*
exit /b %ERRORLEVEL%
