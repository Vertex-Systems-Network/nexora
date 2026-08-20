<?php

declare(strict_types=1);

/** @return array{handle:resource,path:string} */
function nexoraAcquireTargetExecutionLock(string $root,string $runId): array
{
    $dir=$root.'/storage/app/nexora/n1-target-execution';if(!is_dir($dir)&&!mkdir($dir,0775,true)&&!is_dir($dir))throw new RuntimeException('Unable to create target execution directory.');
    $path=$dir.'/execution.lock';$handle=fopen($path,'c+');if($handle===false)throw new RuntimeException('Unable to open target execution lock.');
    if(!flock($handle,LOCK_EX|LOCK_NB)){fclose($handle);throw new RuntimeException('Another N1.0 target execution is already active. Wait for it to finish before starting another run.');}
    ftruncate($handle,0);rewind($handle);fwrite($handle,json_encode(['schema'=>1,'run_id'=>$runId,'pid'=>getmypid(),'started_at'=>gmdate(DATE_ATOM)],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);fflush($handle);
    return ['handle'=>$handle,'path'=>$path];
}

function nexoraReleaseTargetExecutionLock(array $lock): void
{
    $handle=$lock['handle']??null;if(is_resource($handle)){flock($handle,LOCK_UN);fclose($handle);}
}

function nexoraTargetExecutionLockActive(string $root): bool
{
    $path=$root.'/storage/app/nexora/n1-target-execution/execution.lock';if(!is_file($path))return false;$h=@fopen($path,'c+');if($h===false)return true;$ok=@flock($h,LOCK_EX|LOCK_NB);if($ok){@flock($h,LOCK_UN);fclose($h);return false;}fclose($h);return true;
}
