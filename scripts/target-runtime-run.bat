@echo off
setlocal
cd /d "%~dp0\.."
php scripts\target-runtime-run.php %*
exit /b %ERRORLEVEL%
