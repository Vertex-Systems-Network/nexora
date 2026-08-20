<?php

declare(strict_types=1);

/** @return list<string> */
function nexoraValidateZipArchiveHygiene(string $root, string $path): array
{
    $errors=[];
    if (! is_file($path)) return ['archive missing: '.basename($path)];
    if (! class_exists(ZipArchive::class)) return ['PHP ext-zip required for archive hygiene verification'];
    $config=require $root.'/config/nexora-release-trust.php';
    $policy=(array)($config['archive']??[]);
    $maxEntries=max(1,(int)($policy['max_entries']??20000));
    $maxEntry=max(1,(int)($policy['max_entry_bytes']??268435456));
    $maxTotal=max(1,(int)($policy['max_total_uncompressed_bytes']??2147483648));
    $maxRatio=max(1,(int)($policy['max_compression_ratio']??250));
    $zip=new ZipArchive();
    if ($zip->open($path,ZipArchive::RDONLY)!==true) return ['archive cannot be opened: '.basename($path)];
    if ($zip->numFiles>$maxEntries) $errors[]="archive entry count {$zip->numFiles} exceeds {$maxEntries}";
    $seen=[];$seenCase=[];$total=0;
    for($i=0;$i<$zip->numFiles;$i++){
        $stat=$zip->statIndex($i);if(!is_array($stat)){ $errors[]="unable to stat archive entry index {$i}";continue; }
        $name=str_replace('\\','/',(string)($stat['name']??''));
        if($name===''||str_contains($name,"\0")){$errors[]="invalid empty/NUL archive path at index {$i}";continue;}
        $parts=explode('/',$name);
        if(($policy['reject_unsafe_paths']??true)===true && (str_starts_with($name,'/')||preg_match('/^[A-Za-z]:\//',$name)===1||in_array('..',$parts,true)||in_array('.',$parts,true)))$errors[]="unsafe archive path [{$name}]";
        if(isset($seen[$name]))$errors[]="duplicate archive path [{$name}]";$seen[$name]=true;
        $case=strtolower($name);if(($policy['reject_case_collisions']??true)===true && isset($seenCase[$case]) && $seenCase[$case]!==$name)$errors[]="case-colliding archive paths [{$seenCase[$case]}] and [{$name}]";$seenCase[$case]=$name;
        $size=(int)($stat['size']??0);$comp=(int)($stat['comp_size']??0);$total+=$size;
        if($size>$maxEntry)$errors[]="archive entry exceeds per-file ceiling [{$name}]";
        if($size>=1048576 && $comp>0 && ($size/$comp)>$maxRatio)$errors[]="archive compression ratio exceeds ceiling [{$name}]";
        if(($policy['reject_symlinks']??true)===true){$opsys=0;$attrs=0;if(method_exists($zip,'getExternalAttributesIndex')){@$zip->getExternalAttributesIndex($i,$opsys,$attrs);}if((($attrs>>16)&0170000)===0120000)$errors[]="archive symbolic link rejected [{$name}]";}
    }
    if($total>$maxTotal)$errors[]="archive total uncompressed bytes {$total} exceeds {$maxTotal}";
    $zip->close();
    return array_values(array_unique($errors));
}
