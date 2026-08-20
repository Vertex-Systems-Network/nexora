<?php

declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/scripts/lib/final-evidence.php';
$path=(string)(getenv('NEXORA_BACKUP_RESTORE_EVIDENCE') ?: $root.'/storage/app/nexora/certification/backup-restore-evidence.json');
$data=nexoraEvidenceJson($path);
if($data===null){fwrite(STDERR,"[Nexora Backup/Restore Evidence] Missing or invalid evidence: {$path}\nCopy docs/backup-restore-evidence.example.json and record a real disposable-target rehearsal.\n");exit(1);}
$errors=nexoraValidateBackupRestoreEvidence($root,$data);
if($errors!==[]){fwrite(STDERR,"[Nexora Backup/Restore Evidence] FAIL\n - ".implode("\n - ",$errors)."\n");exit(1);}
fwrite(STDOUT,"[Nexora Backup/Restore Evidence] PASS — checksum-sealed backup restored to a disposable target and post-restore health/data checks passed.\n");
