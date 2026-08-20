@echo off
setlocal
php "%~dp0n1-target-execution.php" %*
exit /b %errorlevel%
