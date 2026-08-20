$script = Join-Path $PSScriptRoot 'n1-target-fast-track.php'
& php $script @args
exit $LASTEXITCODE
