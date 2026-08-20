$ErrorActionPreference = 'Stop'
& php "$PSScriptRoot/target-prerequisite-remediate.php" @args
exit $LASTEXITCODE
