@echo off
setlocal
cd /d "%~dp0\.."
php scripts\target-certification-orchestrator.php %*
exit /b %ERRORLEVEL%
