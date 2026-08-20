<?php

declare(strict_types=1);

/** @return array{ok:bool,errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeInertiaFrontendContracts(string $root): array
{
    $errors=[]; $warnings=[];
    $base=$root.'/resources/js/admin';
    $files=[];
    $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS));
    foreach($iterator as $file){
        if(!$file->isFile() || !in_array(strtolower($file->getExtension()),['ts','tsx'],true)) continue;
        $files[]=$file->getPathname();
    }

    $transformChains=0; $unsafeRouterPayloads=0; $navLinkChildren=0; $unsafeUseFormUnknown=0;
    foreach($files as $path){
        $source=(string)file_get_contents($path);
        $relative=str_replace('\\','/',substr($path,strlen($root)+1));

        // Inertia 3.x transform() returns void. Method chaining is therefore always invalid.
        if(preg_match_all('/\.transform\s*\([^;]*?\)\s*\.\s*(?:post|put|patch|delete|get)\s*\(/s',$source,$m)>0){
            $count=count($m[0]); $transformChains+=$count; $errors[]="{$relative}: {$count} chained useForm transform/submit call(s)";
        }

        // RequestPayload accepts FormDataConvertible, not arbitrary unknown values.
        if(preg_match_all('/(?:router\.(?:post|put|patch)|const\s+\w+\s*=\s*\([^)]*data\s*:\s*)[^\n]{0,240}Record<string\s*,\s*unknown>/',$source,$m)>0){
            $count=count($m[0]); $unsafeRouterPayloads+=$count; $errors[]="{$relative}: {$count} router payload boundary/boundaries use Record<string, unknown>";
        }

        // Untitled NavLink deliberately has label+icon props and no children.
        if(preg_match_all('/<NavLink\b(?![^>]*\/>)((?:.|\n)*?)<\/NavLink>/U',$source,$m)>0){
            $count=count($m[0]); $navLinkChildren+=$count; $errors[]="{$relative}: {$count} NavLink child-content use(s); use label+icon props or another UI-library link component";
        }

        // A Record<string, unknown> nested in the immediate useForm declaration is incompatible with FormDataType<T>.
        if(preg_match_all('/useForm(?:<[^;>]+>)?\s*\([^;\n]{0,900}Record<string\s*,\s*unknown>/s',$source,$m)>0){
            $count=count($m[0]); $unsafeUseFormUnknown+=$count; $errors[]="{$relative}: {$count} useForm boundary/boundaries contain Record<string, unknown>";
        }
    }

    $requiredMarkers=[
        'resources/js/admin/pages/Admin/Automation/Form.tsx'=>['WorkflowScalar','type TriggerConfig = Record<string, WorkflowScalar>','useForm<WorkflowFormData>'],
        'resources/js/admin/pages/Admin/Cloud/Index.tsx'=>['RequestPayload'],
        'resources/js/admin/pages/Admin/Discovery/Index.tsx'=>['RequestPayload'],
        'resources/js/admin/pages/Admin/Documents/Form.tsx'=>['DocumentContent','documentError','Deliberate shallow boundary: DocumentContent contains recursive WriterValue nodes.','content: any;'],
        'resources/js/admin/components/writer/BlockEditor.tsx'=>['WriterValue','Record<string, WriterValue>'],
        'resources/js/admin/pages/Admin/Enterprise/OrganizationShow.tsx'=>['Deliberate shallow boundary: SSO configuration and secret payload default server-side.','const ssoForm = useForm({'],
        'resources/js/admin/pages/Admin/Helpdesk/_HelpdeskNav.tsx'=>['ButtonLink'],
        'resources/js/admin/pages/Admin/Membership/_MembershipNav.tsx'=>['ButtonLink'],
    ];
    foreach($requiredMarkers as $relative=>$markers){
        $path=$root.'/'.$relative;
        if(!is_file($path)){ $errors[]="missing Laragon build-fix target {$relative}"; continue; }
        $source=(string)file_get_contents($path);
        foreach($markers as $marker) if(!str_contains($source,$marker)) $errors[]="{$relative}: missing frontend type-contract marker [{$marker}]";
    }

    // Exact known Inertia 3 transform sites must use a separate submit statement.
    foreach([
        'resources/js/admin/pages/Admin/Distribution/Index.tsx',
        'resources/js/admin/pages/Admin/Media/Index.tsx',
        'resources/js/admin/pages/Admin/Publishing/ArticleSettings.tsx',
        'resources/js/admin/pages/Admin/Studio/Editor.tsx',
    ] as $relative){
        $source=(string)@file_get_contents($root.'/'.$relative);
        if($source==='' || !str_contains($source,'.transform(')) $warnings[]="{$relative}: expected transform normalization site not found";
    }

    return [
        'ok'=>$errors===[],
        'errors'=>array_values(array_unique($errors)),
        'warnings'=>array_values(array_unique($warnings)),
        'metrics'=>[
            'admin_ts_files'=>count($files),
            'laragon_error_files'=>11,
            'transform_chains'=>$transformChains,
            'unsafe_router_payloads'=>$unsafeRouterPayloads,
            'navlink_children'=>$navLinkChildren,
            'unsafe_useform_unknown'=>$unsafeUseFormUnknown,
        ],
    ];
}
