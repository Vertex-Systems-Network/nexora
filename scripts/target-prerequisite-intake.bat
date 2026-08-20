@echo off
setlocal
cd /d "%~dp0\.."
php scripts\target-prerequisite-intake.php %*
exit /b %errorlevel%
