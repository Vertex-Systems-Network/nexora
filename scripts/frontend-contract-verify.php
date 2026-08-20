<?php

declare(strict_types=1);

$root=dirname(__DIR__);
$errors=[];

$adminRoot=$root.'/resources/js/admin';
$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($adminRoot,FilesystemIterator::SKIP_DOTS));
foreach($iterator as $file){
    if(!$file->isFile() || !in_array(strtolower($file->getExtension()),['ts','tsx'],true)) continue;
    $path=$file->getPathname();
    $source=(string)file_get_contents($path);
    $relative=str_replace('\\','/',substr($path,strlen($root)+1));

    // Inertia React v3 transform mutates the form helper and returns void; chaining a submit method is invalid.
    if(preg_match('/\.transform\s*\([\s\S]*?\)\s*\.\s*(?:get|post|put|patch|delete)\s*\(/m',$source)===1){
        $errors[]="Inertia form transform() must not be chained to a submit call: {$relative}";
    }

}


foreach([
    'resources/js/admin/pages/Admin/Automation/Form.tsx',
    'resources/js/admin/pages/Admin/Enterprise/OrganizationShow.tsx',
] as $typedForm){
    $path=$root.'/'.$typedForm;
    if(!is_file($path)) continue;
    $source=(string)file_get_contents($path);
    if(preg_match('/Record\s*<\s*string\s*,\s*unknown\s*>/',$source)===1){
        $errors[]="Known Inertia v3 typed form regressed to Record<string, unknown>: {$typedForm}";
    }
}


$frontendContracts=[
    'resources/js/admin/pages/Admin/Automation/Form.tsx'=>['WorkflowScalar','useForm<WorkflowFormData>','type WorkflowFormData','type TriggerConfig = Record<string, WorkflowScalar>'],
    'resources/js/admin/pages/Admin/Cloud/Index.tsx'=>['RequestPayload'],
    'resources/js/admin/pages/Admin/Discovery/Index.tsx'=>['RequestPayload'],
    'resources/js/admin/pages/Admin/Documents/Form.tsx'=>['DocumentContent','documentError','content: any;','form.data.content as DocumentContent'],
    'resources/js/admin/components/writer/BlockEditor.tsx'=>['WriterValue','Record<string, WriterValue>'],
    'resources/js/admin/pages/Admin/Enterprise/OrganizationShow.tsx'=>['Deliberate shallow boundary: SSO configuration and secret payload default server-side.','const ssoForm = useForm({'],
    'resources/js/admin/pages/Admin/Helpdesk/_HelpdeskNav.tsx'=>['ButtonLink'],
    'resources/js/admin/pages/Admin/Membership/_MembershipNav.tsx'=>['ButtonLink'],
];
foreach($frontendContracts as $relative=>$markers){
    $path=$root.'/'.$relative;if(!is_file($path)){$errors[]="Known frontend regression target missing: {$relative}";continue;}$source=(string)file_get_contents($path);
    foreach($markers as $marker)if(!str_contains($source,$marker))$errors[]="Known Inertia v3 regression marker missing [{$marker}]: {$relative}";
}
foreach([
    'resources/js/admin/pages/Admin/Distribution/Index.tsx',
    'resources/js/admin/pages/Admin/Media/Index.tsx',
    'resources/js/admin/pages/Admin/Publishing/ArticleSettings.tsx',
    'resources/js/admin/pages/Admin/Studio/Editor.tsx',
] as $relative){
    $source=(string)file_get_contents($root.'/'.$relative);
    if(preg_match('/\.transform\s*\([\s\S]*?\)\s*\.\s*(?:post|put|patch|delete)\s*\(/m',$source)===1)$errors[]="Known Inertia v3 transform-chain regression returned: {$relative}";
}

$runtime=$root.'/app/Http/Middleware/RuntimeNodeHeartbeat.php';
if(!is_file($runtime)){
    $errors[]='RuntimeNodeHeartbeat middleware is missing.';
}else{
    $source=(string)file_get_contents($runtime);
    if(preg_match('/function\s+handle\s*\(\s*Request\s+\$request\s*,\s*Closure\s+\$next\s*\)\s*:\s*Response/',$source)!==1){
        $errors[]='RuntimeNodeHeartbeat::handle must use the standard two-argument Laravel middleware signature.';
    }
    foreach(['private readonly NodeIdentity $identity','private readonly NodeManager $nodes','private readonly InstallationState $installation'] as $dependency){
        if(!str_contains($source,$dependency)) $errors[]="RuntimeNodeHeartbeat must constructor-inject {$dependency}.";
    }
    if(!str_contains($source, 'if (! $this->installation->isInstalled())')){
        $errors[]='RuntimeNodeHeartbeat must bypass readiness fencing before Nexora is installed.';
    }
}

foreach([
    'resources/js/admin/pages/Admin/Helpdesk/_HelpdeskNav.tsx',
    'resources/js/admin/pages/Admin/Membership/_MembershipNav.tsx',
] as $nav){
    $path=$root.'/'.$nav;
    if(!is_file($path)) continue;
    $source=(string)file_get_contents($path);
    if(str_contains($source,'NavLink')) $errors[]="Horizontal section navigation must use ButtonLink rather than sidebar NavLink: {$nav}";
}

if($errors!==[]){
    fwrite(STDERR,"[Nexora Frontend Contract] FAILED\n - ".implode("\n - ",$errors)."\n");
    exit(1);
}

fwrite(STDOUT,"[Nexora Frontend Contract] PASS\n");
