@echo off
setlocal
cd /d "%~dp0.."
php scripts\n1-c3-database-matrix-certify.php %*
exit /b %errorlevel%
