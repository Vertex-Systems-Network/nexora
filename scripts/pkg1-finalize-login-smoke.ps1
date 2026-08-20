param(
    [Parameter(Mandatory=$true, Position=0)][string]$Operator,
    [Parameter(Mandatory=$false, Position=1)][string]$BaseUrl = 'http://nexora'
)

$ErrorActionPreference = 'Stop'
Set-Location (Resolve-Path (Join-Path $PSScriptRoot '..'))
$secure = Read-Host 'Nexora installer Super Admin password' -AsSecureString
$bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
try {
    $plain = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr)
    $env:NEXORA_PKG1_SMOKE_PASSWORD = $plain
    & php 'scripts/pkg1-usable-closure.php' "--operator=$Operator" "--base-url=$BaseUrl"
    $code = $LASTEXITCODE
} finally {
    Remove-Item Env:NEXORA_PKG1_SMOKE_PASSWORD -ErrorAction SilentlyContinue
    if ($bstr -ne [IntPtr]::Zero) {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
    }
    $plain = $null
    $secure = $null
}
exit $code
