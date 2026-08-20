@echo off
setlocal
if "%~1"=="" (
  echo Usage: scripts\pkg1-finalize-login-smoke.bat "REAL NAME" [base-url]
  exit /b 2
)
set "NX_BASE=%~2"
if "%NX_BASE%"=="" set "NX_BASE=http://nexora"
set "NEXORA_PKG1_FINALIZER=%~dp0pkg1-finalize-login-smoke.ps1"

powershell -NoProfile -ExecutionPolicy Bypass -Command "$tokens=$null; $parseErrors=$null; $null=[System.Management.Automation.Language.Parser]::ParseFile($env:NEXORA_PKG1_FINALIZER,[ref]$tokens,[ref]$parseErrors); if ($parseErrors.Count -gt 0) { foreach ($item in $parseErrors) { Write-Error $item.Message }; exit 3 }"
if errorlevel 1 exit /b %ERRORLEVEL%

powershell -NoProfile -ExecutionPolicy Bypass -File "%NEXORA_PKG1_FINALIZER%" "%~1" "%NX_BASE%"
exit /b %ERRORLEVEL%
