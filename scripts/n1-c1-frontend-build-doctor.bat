@echo off
setlocal
cd /d "%~dp0.."
php scripts\n1-c1-frontend-build-doctor.php %*
exit /b %errorlevel%
