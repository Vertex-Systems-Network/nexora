@echo off
setlocal
cd /d "%~dp0.."
php scripts\pkg1-usable-closure.php %*
exit /b %ERRORLEVEL%
