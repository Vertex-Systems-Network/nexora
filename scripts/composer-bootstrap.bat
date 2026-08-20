@echo off
setlocal
php "%~dp0composer-bootstrap.php" %*
exit /b %ERRORLEVEL%
