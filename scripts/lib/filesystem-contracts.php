<?php

declare(strict_types=1);

/** @return array{ok:bool,errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeFilesystemContracts(string $root): array
{
    $errors=[];$warnings=[];
    $root=rtrim($root,"/\\");
    $excludedPrefixes=['vendor/','node_modules/','.git/','public/build/','storage/app/nexora/certification/','storage/app/nexora/target-diagnostics/'];
    $caseSeen=[];$entries=0;$maxPath=0;$caseCollisions=0;$windowsInvalid=0;
    $reserved=['con','prn','aux','nul','com1','com2','com3','com4','com5','com6','com7','com8','com9','lpt1','lpt2','lpt3','lpt4','lpt5','lpt6','lpt7','lpt8','lpt9'];
    $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST);
    foreach($iterator as $item){
        $relative=str_replace('\\','/',substr($item->getPathname(),strlen($root)+1));
        $skip=false;foreach($excludedPrefixes as $prefix){if($relative===rtrim($prefix,'/')||str_starts_with($relative,$prefix)){$skip=true;break;}}if($skip)continue;
        $entries++;$maxPath=max($maxPath,strlen($relative));
        $folded=strtolower($relative);
        if(isset($caseSeen[$folded])&&$caseSeen[$folded]!==$relative){$errors[]="Case-insensitive repository path collision [{$relative}] versus [{$caseSeen[$folded]}].";$caseCollisions++;}else{$caseSeen[$folded]=$relative;}
        foreach(explode('/',$relative) as $component){
            $stem=strtolower(explode('.',$component,2)[0]);
            if(in_array($stem,$reserved,true)||str_ends_with($component,'.')||str_ends_with($component,' ')||str_contains($component,':')){$errors[]="Repository path is not Windows-portable [{$relative}].";$windowsInvalid++;break;}
        }
    }

    $maxAllowed=200;
    $configPath=$root.'/config/nexora-filesystem.php';
    if(!is_file($configPath))$errors[]='Missing config/nexora-filesystem.php.';
    else{
        $source=(string)file_get_contents($configPath);
        if(preg_match("/'repository_max_relative_path'\\s*=>\\s*(\\d+)/",$source,$match))$maxAllowed=(int)$match[1];
        foreach(['required_writable_directories','protected_local_directories','atomic_state_files'] as $marker)if(!str_contains($source,"'{$marker}'"))$errors[]="Filesystem config missing [{$marker}].";
    }
    if($maxPath>$maxAllowed)$errors[]="Repository relative path length {$maxPath} exceeds portability budget {$maxAllowed}.";

    $psr4Classes=0;
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app',FilesystemIterator::SKIP_DOTS)) as $file){
        if(!$file->isFile()||strtolower($file->getExtension())!=='php')continue;
        $source=(string)file_get_contents($file->getPathname());
        if(!preg_match('/\\bnamespace\\s+([^;]+);/',$source,$namespace)||!preg_match('/\\b(?:final\\s+|abstract\\s+|readonly\\s+)*(?:class|interface|trait|enum)\\s+([A-Za-z_][A-Za-z0-9_]*)/',$source,$class))continue;
        $relative=str_replace('\\','/',substr($file->getPathname(),strlen($root.'/app')+1));
        $without=substr($relative,0,-4);$parts=explode('/',$without);$expectedClass=array_pop($parts);$expectedNamespace='App'.($parts?'\\'.implode('\\',$parts):'');
        $psr4Classes++;
        if(trim($namespace[1])!==$expectedNamespace||$class[1]!==$expectedClass)$errors[]="PSR-4 case/path mismatch [{$relative}] declares {$namespace[1]}\\{$class[1]}.";
    }

    $appImports=0;
    foreach(['app','tests'] as $scope){
        if(!is_dir($root.'/'.$scope))continue;
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$scope,FilesystemIterator::SKIP_DOTS)) as $file){
            if(!$file->isFile()||strtolower($file->getExtension())!=='php')continue;
            $source=(string)file_get_contents($file->getPathname());
            if(!preg_match_all('/^use\\s+(App\\\\[A-Za-z0-9_\\\\]+)(?:\\s+as\\s+\\w+)?;/m',$source,$matches))continue;
            foreach($matches[1] as $fqcn){$appImports++;$target=$root.'/'.str_replace(['App\\','\\'],['app/','/'],$fqcn).'.php';if(!is_file($target))$errors[]="Case-sensitive App import target missing [{$fqcn}] referenced by ".basename($file->getPathname()).'.';}
        }
    }

    foreach(['app','bootstrap','config','database','routes','scripts','public'] as $scope){
        $base=$root.'/'.$scope;if(!is_dir($base))continue;
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS)) as $file){
            if(!$file->isFile()||strtolower($file->getExtension())!=='php')continue;
            $source=(string)file_get_contents($file->getPathname());
            if(preg_match('/\\b(?:base|storage|public|database)_path\\(\\s*[\'\"][^\'\"]*\\\\[^\'\"]*[\'\"]/',$source)===1)$errors[]='Framework path helper contains a hard-coded backslash separator: '.str_replace('\\','/',substr($file->getPathname(),strlen($root)+1));
        }
    }

    $atomic=$root.'/app/Nexora/Foundation/Filesystem/AtomicFileWriter.php';
    $portable=$root.'/app/Nexora/Foundation/Filesystem/PortablePath.php';
    $doctor=$root.'/app/Nexora/Foundation/Filesystem/FilesystemDoctor.php';
    foreach([$atomic,$portable,$doctor,$root.'/app/Console/Commands/Nexora/FilesystemDoctorCommand.php'] as $path)if(!is_file($path))$errors[]='Missing RC16 filesystem artifact '.basename($path).'.';
    $atomicSource=is_file($atomic)?(string)file_get_contents($atomic):'';
    foreach(['fsync','moveVerified','hash_file(\'sha256\'','PHP_OS_FAMILY','is_link($path)'] as $marker)if(!str_contains($atomicSource,$marker))$errors[]="AtomicFileWriter missing [{$marker}] portability/safety behavior.";
    $portableSource=is_file($portable)?(string)file_get_contents($portable):'';
    foreach(['WINDOWS_RESERVED','normalizeRelative','assertNoExistingSymlinkTraversal','DIRECTORY_SEPARATOR'] as $marker)if(!str_contains($portableSource,$marker))$errors[]="PortablePath missing [{$marker}].";

    $critical=[
        'app/Nexora/Installation/InstallationState.php',
        'app/Nexora/Installation/EnvironmentWriter.php',
        'app/Nexora/Foundation/Upgrade/UpgradePlanStore.php',
        'app/Nexora/Cloud/Services/NodeIdentity.php',
        'app/Nexora/Installation/DatabaseBackupManager.php',
    ];
    foreach($critical as $relative){$source=is_file($root.'/'.$relative)?(string)file_get_contents($root.'/'.$relative):'';if(!str_contains($source,'AtomicFileWriter'))$errors[]="Critical state writer does not use AtomicFileWriter [{$relative}].";}

    $journal=(string)@file_get_contents($root.'/app/Nexora/Installation/InstallationRunControl.php');
    foreach(['flock($handle, LOCK_EX)','fflush($handle)','fsync($handle)'] as $marker)if(!str_contains($journal,$marker))$errors[]="Mutable installation journal missing [{$marker}] durability contract.";

    $deployment=(string)@file_get_contents($root.'/public/nexora-bootstrap.php');
    foreach(['function nxAtomicWriteFile','fsync($handle)','deployment-last-interrupted.json','deployment-last-run.json'] as $marker)if(!str_contains($deployment,$marker))$errors[]="Pre-Laravel deployment bootstrap missing [{$marker}] atomic state contract.";
    if(str_contains($deployment,"@file_put_contents(nxDeploymentStatePath"))$errors[]='Deployment state still bypasses nxAtomicWriteFile.';

    $installerBootstrap=(string)@file_get_contents($root.'/bootstrap/nexora-installer-bootstrap.php');
    foreach(['.nexora-bootstrap-key-','fsync($handle)','rename($temporaryKey, $bootstrapKey)'] as $marker)if(!str_contains($installerBootstrap,$marker))$errors[]="Installer bootstrap key persistence missing [{$marker}].";

    foreach(['app/Nexora/Themes/Services/ThemePackageInstaller.php','app/Nexora/Extensions/Services/ExtensionPackageInstaller.php'] as $relative){
        $source=(string)@file_get_contents($root.'/'.$relative);
        foreach(['PortablePath','case-insensitive path collision','getExternalAttributesIndex'] as $marker)if(!str_contains($source,$marker))$errors[]="Package installer missing [{$marker}] portability boundary [{$relative}].";
    }

    return ['ok'=>$errors===[],'errors'=>array_values(array_unique($errors)),'warnings'=>$warnings,'metrics'=>[
        'repository_entries'=>$entries,'max_relative_path'=>$maxPath,'case_collisions'=>$caseCollisions,'windows_invalid_paths'=>$windowsInvalid,'psr4_classes'=>$psr4Classes,'app_imports'=>$appImports,
    ]];
}
