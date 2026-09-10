param(
    [Parameter(Mandatory = $true)]
    [string]$Target,

    [switch]$Apply,

    [string]$Confirm = ''
)

$ErrorActionPreference = 'Stop'
$scriptPath = Join-Path $PSScriptRoot 'rc93-post-install-identity-repair.php'

if (-not (Test-Path -LiteralPath $scriptPath -PathType Leaf)) {
    throw "Missing repair script: $scriptPath"
}

$php = Get-Command php -ErrorAction Stop
$arguments = @($scriptPath, "--target=$Target")

if ($Apply) {
    if ($Confirm -ne 'REPAIR-RC93') {
        throw 'Apply mode requires -Confirm REPAIR-RC93.'
    }
    $arguments += '--apply'
    $arguments += '--confirm=REPAIR-RC93'
} elseif ($Confirm -ne '') {
    throw '-Confirm is only valid together with -Apply.'
}

& $php.Source @arguments
exit $LASTEXITCODE
