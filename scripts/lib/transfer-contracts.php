<?php

declare(strict_types=1);

/** @return array{ok:bool,errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeTransferContracts(string $root): array
{
    $errors=[];$warnings=[];
    $required=[
        'config/nexora-transfers.php',
        'app/Nexora/Foundation/Transfers/TransferSafety.php',
        'app/Nexora/Foundation/Transfers/TransferDoctor.php',
        'app/Console/Commands/Nexora/TransferDoctorCommand.php',
        'app/Nexora/Media/Services/MediaManager.php',
        'app/Nexora/Security/Sentinel/Support/QuarantineManager.php',
        'app/Nexora/Extensions/Services/MarketplacePackageStager.php',
        'app/Nexora/Extensions/Services/ExtensionPackageInstaller.php',
        'app/Nexora/Themes/Services/ThemePackageInstaller.php',
        'app/Nexora/Installation/DatabaseBackupManager.php',
        'app/Nexora/Cloud/Services/BackupOrchestrator.php',
    ];
    foreach($required as $relative)if(!is_file($root.'/'.$relative)||filesize($root.'/'.$relative)===0)$errors[]="Missing RC17 transfer artifact [{$relative}].";

    $config=(string)@file_get_contents($root.'/config/nexora-transfers.php');
    foreach(['temporary_root','stream_chunk_bytes','minimum_free_bytes','max_upload_bytes','max_download_bytes','max_total_uncompressed_bytes','max_entry_uncompressed_bytes','max_compression_ratio','max_bytes'] as $marker)if(!str_contains($config,"'{$marker}'"))$errors[]="Transfer policy missing [{$marker}].";

    $safety=(string)@file_get_contents($root.'/app/Nexora/Foundation/Transfers/TransferSafety.php');
    foreach(['disk_free_space','copyStream','copyFileAtomically','copyStreamAtomically','hashStream','assertArchiveBudget','fsync','moveVerified','max_compression_ratio'] as $marker)if(!str_contains($safety,$marker))$errors[]="TransferSafety missing [{$marker}] bounded/durable behavior.";
    if(!preg_match('/while\s*\(\s*\$offset\s*<\s*\$length\s*\)/',$safety))$errors[]='TransferSafety must handle partial fwrite() results instead of assuming one write completes the chunk.';

    $media=(string)@file_get_contents($root.'/app/Nexora/Media/Services/MediaManager.php');
    foreach(['assertLocalCapacity','readStream','hashStream','size($path)','Storage::disk($mediaDisk)->delete($path)'] as $marker)if(!str_contains($media,$marker))$errors[]="Media upload flow missing [{$marker}] transfer integrity behavior.";

    $variants=(string)@file_get_contents($root.'/app/Nexora/Media/Services/ImageVariantGenerator.php');
    if(!str_contains($variants,'variant_decode_max_bytes'))$errors[]='Image variants must bound in-memory GD decode by configured source bytes.';

    $quarantine=(string)@file_get_contents($root.'/app/Nexora/Security/Sentinel/Support/QuarantineManager.php');
    foreach(['TransferSafety','copyFileAtomically','max_kilobytes'] as $marker)if(!str_contains($quarantine,$marker))$errors[]="Quarantine transfer missing [{$marker}].";
    if(preg_match('/\b(?:copy|rename)\s*\(\s*\$sourcePath\s*,\s*\$path/',$quarantine)===1)$errors[]='Quarantine package publication must not bypass bounded atomic transfer safety.';

    $market=(string)@file_get_contents($root.'/app/Nexora/Extensions/Services/MarketplacePackageStager.php');
    foreach(['temporaryPath','max_download_bytes','progress','Content-Length','assertSourceFile'] as $marker)if(!str_contains($market,$marker))$errors[]="Marketplace staging missing [{$marker}] bounded-download behavior.";
    if(str_contains($market,'sys_get_temp_dir()')||str_contains($market,'tempnam('))$errors[]='Marketplace package staging must use protected Nexora transfer storage, not ambient system temp.';

    $theme=(string)@file_get_contents($root.'/app/Nexora/Themes/Services/ThemePackageInstaller.php');
    foreach(['assertArchiveBudget','getStream(','copyStreamAtomically','max_text_entry_bytes'] as $marker)if(!str_contains($theme,$marker))$errors[]="Theme installer missing [{$marker}] bounded archive behavior.";
    if(str_contains($theme,'getFromIndex('))$errors[]='Theme extraction must not inflate arbitrary archive files into PHP memory via getFromIndex().';

    $extension=(string)@file_get_contents($root.'/app/Nexora/Extensions/Services/ExtensionPackageInstaller.php');
    foreach(['assertArchiveBudget','getStream(','copyStreamAtomically','atomically publish'] as $marker)if(!str_contains($extension,$marker))$errors[]="Extension installer missing [{$marker}] bounded archive behavior.";
    if(str_contains($extension,'stream_copy_to_stream(')||str_contains($extension,'File::copyDirectory('))$errors[]='Extension publication must not use unbounded stream copy or partial directory-copy fallback.';

    $backup=(string)@file_get_contents($root.'/app/Nexora/Cloud/Services/BackupOrchestrator.php');
    foreach(['readStream','hashStream','assertLocalCapacity','size($target)','Storage::disk($backupDisk)->delete($target)'] as $marker)if(!str_contains($backup,$marker))$errors[]="Runtime backup flow missing [{$marker}] streaming/integrity behavior.";
    if(str_contains($backup,'file_get_contents(')||preg_match('/Storage::disk\([^)]*\)->get\(/',$backup)===1)$errors[]='Runtime backups must never load the complete backup artifact into PHP memory.';

    $dbBackup=(string)@file_get_contents($root.'/app/Nexora/Installation/DatabaseBackupManager.php');
    foreach(['siblingTemporaryPath','fflush','fsync','moveVerified','assertBackupIntegrity','Database backup exceeded the configured maximum size'] as $marker)if(!str_contains($dbBackup,$marker))$errors[]="Database backup flow missing [{$marker}] partial-file/disk-full safety.";
    if(str_contains($dbBackup,'fopen($path, \'wb\')'))$errors[]='Database backups must write to staging files before atomic publication.';

    $cloud=(string)@file_get_contents($root.'/app/Http/Controllers/Admin/Cloud/CloudOperationsController.php');
    if(!str_contains($cloud,'$backups->verify($backup)')||!str_contains($cloud,'->download('))$errors[]='Cloud backup download must verify integrity and use a streamed filesystem download response.';
    $installer=(string)@file_get_contents($root.'/app/Http/Controllers/Install/InstallerController.php');
    if(!str_contains($installer,'response()->download(')||!str_contains($installer,"'Cache-Control' => 'no-store, private'"))$errors[]='Installer backup download must remain a no-store binary-file response.';
    $publicMedia=(string)@file_get_contents($root.'/app/Http/Controllers/Public/MediaController.php');
    if(!str_contains($publicMedia,'Storage::disk($asset->disk)->response('))$errors[]='Public media delivery must stream through the configured filesystem response surface.';

    $fsConfig=(string)@file_get_contents($root.'/config/nexora-filesystem.php');
    if(substr_count($fsConfig,"storage_path('app/nexora/transfers')")<2)$errors[]='Transfer staging must be both writable and protected in filesystem policy.';

    // Count complete backup-artifact memory loads only. Small JSON metadata reads are not
    // backup payload loads and must not create a false-positive RC failure.
    $unsafeBackupLoads=substr_count($backup,'file_get_contents(')+preg_match_all('/Storage::disk\([^)]*\)->get\(/',$backup);
    $unsafeBackupLoads+=preg_match_all('/file_get_contents\(\s*\$(?:backupPath|backup|artifact|sourcePath)\s*\)/',$dbBackup);
    $unboundedArchiveExtracts=substr_count($theme,'getFromIndex(')+substr_count($extension,'stream_copy_to_stream(')+substr_count($extension,'File::copyDirectory(');

    return [
        'ok'=>$errors===[],
        'errors'=>array_values(array_unique($errors)),
        'warnings'=>array_values(array_unique($warnings)),
        'metrics'=>[
            'transfer_surfaces'=>7,
            'unsafe_backup_full_loads'=>$unsafeBackupLoads,
            'unbounded_archive_extracts'=>$unboundedArchiveExtracts,
            'archive_budget_profiles'=>2,
        ],
    ];
}
