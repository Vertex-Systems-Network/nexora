<?php

declare(strict_types=1);

/** @return array{ok:bool,errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeRuntimeSafetyContracts(string $root): array
{
    $errors=[];$warnings=[];
    $required=[
        'config/nexora-runtime.php',
        'app/Nexora/Foundation/Runtime/RuntimeLimitsDoctor.php',
        'app/Console/Commands/Nexora/RuntimeDoctorCommand.php',
        'app/Http/Middleware/ConfigureTrustedProxies.php',
        'app/Http/Middleware/EnforceRequestLimits.php',
        'app/Nexora/Installation/SystemRequirementChecker.php',
        'app/Providers/AppServiceProvider.php',
        'app/Nexora/Discovery/Crawler/SeoCrawler.php',
        'app/Http/Controllers/Admin/Discovery/DiscoveryController.php',
        'resources/js/admin/pages/Admin/Discovery/Crawl.tsx',
    ];
    foreach($required as $relative)if(!is_file($root.'/'.$relative)||filesize($root.'/'.$relative)===0)$errors[]="Missing RC18 runtime-safety artifact [{$relative}].";

    $config=(string)@file_get_contents($root.'/config/nexora-runtime.php');
    foreach(['max_body_bytes','trusted_proxies','minimum_memory_bytes','minimum_post_bytes','minimum_upload_bytes','minimum_execution_seconds','minimum_input_seconds','minimum_input_vars','minimum_file_uploads','max_job_timeout_seconds','retry_after_margin_seconds','worker_timeout_seconds','worker_max_time_seconds','worker_restart_memory_mb'] as $marker)if(!str_contains($config,"'{$marker}'"))$errors[]="Runtime safety policy missing [{$marker}].";
    if(str_contains($config,"NEXORA_TRUSTED_PROXIES', '*"))$errors[]='Trusted proxy policy must not default to wildcard trust.';

    $queue=(string)@file_get_contents($root.'/config/queue.php');
    foreach(['DB_QUEUE_RETRY_AFTER', 'REDIS_QUEUE_RETRY_AFTER', 'BEANSTALKD_QUEUE_RETRY_AFTER'] as $key){
        if(!preg_match("/env\\('".preg_quote($key,'/')."',\\s*(\\d+)\\)/",$queue,$m)){$errors[]="Queue retry-after default missing for {$key}.";continue;}
        if((int)$m[1]<1860)$errors[]="{$key} must default to at least 1860 seconds so it exceeds the 1800-second crawl timeout.";
    }

    $bootstrap=(string)@file_get_contents($root.'/bootstrap/app.php');
    foreach(['ConfigureTrustedProxies::class','EnforceRequestLimits::class','$middleware->prepend'] as $marker)if(!str_contains($bootstrap,$marker))$errors[]="Bootstrap missing [{$marker}] early runtime request safety.";

    $limitMiddleware=(string)@file_get_contents($root.'/app/Http/Middleware/EnforceRequestLimits.php');
    foreach(['CONTENT_LENGTH','nexora-runtime.http.max_body_bytes','HttpException(413','ctype_digit'] as $marker)if(!str_contains($limitMiddleware,$marker))$errors[]="Request-limit middleware missing [{$marker}].";

    $proxyMiddleware=(string)@file_get_contents($root.'/app/Http/Middleware/ConfigureTrustedProxies.php');
    foreach(['setTrustedProxies','HEADER_X_FORWARDED_FOR','HEADER_X_FORWARDED_PROTO','proxy === \'*\''] as $marker)if(!str_contains($proxyMiddleware,$marker))$errors[]="Trusted-proxy middleware missing [{$marker}].";

    $provider=(string)@file_get_contents($root.'/app/Providers/AppServiceProvider.php');
    foreach(['Queue::before','Queue::after','Queue::exceptionOccurred','Queue::looping','TenantContext::class','shouldQuit','gc_collect_cycles'] as $marker)if(!str_contains($provider,$marker))$errors[]="Queue worker lifecycle missing [{$marker}].";

    $jobs=glob($root.'/app/Jobs/*.php')?:[];
    $queueJobs=0;$timeouts=[];$jobsWithoutBackoff=0;$jobsWithoutFailOnTimeout=0;
    foreach($jobs as $file){
        $source=(string)file_get_contents($file);
        if(!str_contains($source,'ShouldQueue'))continue;
        $queueJobs++;
        if(!preg_match('/public int \\$timeout = (\\d+);/',$source,$m)){$errors[]='Queue job missing explicit timeout: '.basename($file);continue;}
        $timeout=(int)$m[1];$timeouts[]=$timeout;
        if($timeout>1800)$errors[]='Queue job timeout exceeds RC18 maximum 1800s: '.basename($file);
        if(!preg_match('/public int \\$tries = (\\d+);/',$source,$tries))$errors[]='Queue job missing explicit tries: '.basename($file);
        $triesCount=(int)($tries[1]??0);
        if(!str_contains($source,'public bool $failOnTimeout = true;')){$jobsWithoutFailOnTimeout++;$errors[]='Queue job must fail closed on timeout: '.basename($file);}
        if($triesCount>1&&!str_contains($source,'function backoff()')){$jobsWithoutBackoff++;$errors[]='Retrying queue job requires explicit backoff(): '.basename($file);}
    }
    if($queueJobs!==4)$errors[]="Expected exactly four first-party queue jobs; found {$queueJobs}.";
    if($timeouts!==[]&&max($timeouts)!==1800)$errors[]='Longest first-party queue job timeout must remain exactly 1800 seconds for retry_after policy alignment.';

    $newsletter=(string)@file_get_contents($root.'/app/Jobs/SendNewsletterDelivery.php');
    foreach(['function claim(','lockForUpdate()','newsletter_claim_ttl_seconds',"['sent', 'skipped', 'failed']"] as $marker)if(!str_contains($newsletter,$marker))$errors[]="Newsletter delivery duplicate-worker safety missing [{$marker}].";

    $crawler=(string)@file_get_contents($root.'/app/Nexora/Discovery/Crawler/SeoCrawler.php');
    foreach(['cancelIfRequested','cancel_requested','status\'=>\'cancelled'] as $marker)if(!str_contains($crawler,$marker))$errors[]="SEO crawler graceful cancellation missing [{$marker}].";
    $controller=(string)@file_get_contents($root.'/app/Http/Controllers/Admin/Discovery/DiscoveryController.php');
    foreach(['function cancel(','cancel_requested',"status'=>'cancelled",'seo.crawl.cancel_requested'] as $marker)if(!str_contains($controller,$marker))$errors[]="Discovery cancellation endpoint missing [{$marker}].";
    $routes=(string)@file_get_contents($root.'/routes/web.php');
    if(!str_contains($routes,"/discovery/crawls/{run}/cancel"))$errors[]='SEO crawl cancellation route is missing.';
    $ui=(string)@file_get_contents($root.'/resources/js/admin/pages/Admin/Discovery/Crawl.tsx');
    foreach(['Cancel crawl','cancel_requested','/cancel`'] as $marker)if(!str_contains($ui,$marker))$errors[]="SEO crawl UI cancellation behavior missing [{$marker}].";

    $requirements=(string)@file_get_contents($root.'/app/Nexora/Installation/SystemRequirementChecker.php');
    foreach(['RuntimeLimitsDoctor','runtimeLimits->inspect','Runtime safety:'] as $marker)if(!str_contains($requirements,$marker))$errors[]="Installer preflight missing RC18 runtime-limit enforcement [{$marker}].";

    $doctor=(string)@file_get_contents($root.'/app/Nexora/Foundation/Runtime/RuntimeLimitsDoctor.php');
    foreach(['memory_limit','post_max_size','upload_max_filesize','max_execution_time','max_input_time','max_input_vars','max_file_uploads','queue.retry-after','recommended_worker_command'] as $marker)if(!str_contains($doctor,$marker))$errors[]="Runtime doctor missing [{$marker}].";

    foreach(['.env.example','.env.production.example'] as $envFile){
        $env=(string)@file_get_contents($root.'/'.$envFile);
        foreach(['NEXORA_HTTP_MAX_BODY_BYTES=67108864','NEXORA_TRUSTED_PROXIES=','DB_QUEUE_RETRY_AFTER=1860','NEXORA_QUEUE_WORKER_TIMEOUT=1800','NEXORA_QUEUE_WORKER_MEMORY_MB=384'] as $marker)if(!str_contains($env,$marker))$errors[]="{$envFile} missing RC18 setting [{$marker}].";
    }

    $release=(string)@file_get_contents($root.'/config/nexora-release.php');
    if(!str_contains($release,"'config/nexora-runtime.php'"))$errors[]='Production release policy must require config/nexora-runtime.php.';

    return [
        'ok'=>$errors===[],
        'errors'=>array_values(array_unique($errors)),
        'warnings'=>array_values(array_unique($warnings)),
        'metrics'=>[
            'queue_jobs'=>$queueJobs,
            'max_job_timeout'=>$timeouts===[]?0:max($timeouts),
            'jobs_without_backoff'=>$jobsWithoutBackoff,
            'jobs_without_fail_on_timeout'=>$jobsWithoutFailOnTimeout,
            'request_limit_middlewares'=>2,
            'graceful_cancellation_surfaces'=>3,
        ],
    ];
}
