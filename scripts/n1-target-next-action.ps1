$ErrorActionPreference = "Stop"
& php "$PSScriptRoot/n1-target-next-action.php" @args
exit $LASTEXITCODE
