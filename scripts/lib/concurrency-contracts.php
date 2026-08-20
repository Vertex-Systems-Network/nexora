<?php

declare(strict_types=1);

/** @return array{ok:bool,errors:list<string>,warnings:list<string>,metrics:array<string,int>} */
function nexoraAnalyzeConcurrencyContracts(string $root): array
{
    $errors=[];$warnings=[];
    $required=[
        'config/nexora-concurrency.php',
        'app/Nexora/Foundation/Database/ConcurrencyGuard.php',
        'app/Nexora/Foundation/Database/ConcurrencyDoctor.php',
        'app/Console/Commands/Nexora/ConcurrencyDoctorCommand.php',
        'database/migrations/2026_08_17_000100_add_nexora_concurrency_mutexes.php',
        'app/Nexora/Commerce/Services/PaymentService.php',
        'app/Nexora/Commerce/Services/RefundService.php',
        'app/Nexora/Automation/Services/AutomationEventBus.php',
        'app/Http/Controllers/Public/InboundWebhookController.php',
        'app/Nexora/Distribution/Services/NewsletterDispatchService.php',
        'app/Jobs/SendNewsletterDelivery.php',
        'app/Nexora/Automation/Services/WebhookDeliveryService.php',
        'app/Jobs/ExecuteWorkflowRunJob.php',
        'app/Nexora/Documents/Repositories/DatabaseDocumentRepository.php',
        'app/Nexora/Documents/Services/DocumentRevisionManager.php',
        'app/Nexora/Studio/Services/StudioManager.php',
        'app/Nexora/Publishing/Services/ArticlePublishingManager.php',
    ];
    foreach($required as $relative)if(!is_file($root.'/'.$relative)||filesize($root.'/'.$relative)===0)$errors[]="Missing RC19 concurrency artifact [{$relative}].";

    $config=(string)@file_get_contents($root.'/config/nexora-concurrency.php');
    foreach(['transaction_attempts','workflow_claim_ttl_seconds','webhook_claim_ttl_seconds','newsletter_claim_ttl_seconds','external_effect_semantics','at-least-once','supported_drivers'] as $marker)if(!str_contains($config,$marker))$errors[]="Concurrency policy missing [{$marker}].";

    $guard=(string)@file_get_contents($root.'/app/Nexora/Foundation/Database/ConcurrencyGuard.php');
    foreach(['DB::transaction','lockForUpdate','insertOrIgnore','isUniqueViolation','23505','1062','2601','2627','nx_concurrency_mutexes'] as $marker)if(!str_contains($guard,$marker))$errors[]="ConcurrencyGuard missing [{$marker}].";

    $migration=(string)@file_get_contents($root.'/database/migrations/2026_08_17_000100_add_nexora_concurrency_mutexes.php');
    foreach(["Schema::create('nx_concurrency_mutexes'","->primary()","Schema::dropIfExists('nx_concurrency_mutexes')"] as $marker)if(!str_contains($migration,$marker))$errors[]="Concurrency mutex migration missing [{$marker}].";

    $payment=(string)@file_get_contents($root.'/app/Nexora/Commerce/Services/PaymentService.php');
    foreach(['ConcurrencyGuard','lockForUpdate()->findOrFail($order->id)','lockForUpdate()->findOrFail($invoice->id)','isUniqueViolation','commerce-payment-succeeded:'] as $marker)if(!str_contains($payment,$marker))$errors[]="Payment concurrency protection missing [{$marker}].";
    if(str_contains($payment,'if ($idempotencyKey !== null) {\n            $existing='))$warnings[]='Payment idempotency pre-check appears outside the guarded transaction; inspect manually.';

    $refund=(string)@file_get_contents($root.'/app/Nexora/Commerce/Services/RefundService.php');
    foreach(['ConcurrencyGuard','lockForUpdate()->findOrFail($payment->id)','isUniqueViolation','commerce-refund-created:'] as $marker)if(!str_contains($refund,$marker))$errors[]="Refund concurrency protection missing [{$marker}].";

    $eventBus=(string)@file_get_contents($root.'/app/Nexora/Automation/Services/AutomationEventBus.php');
    foreach(['ConcurrencyGuard','lockForUpdate()->first()','afterCommit()','processed_at','isUniqueViolation'] as $marker)if(!str_contains($eventBus,$marker))$errors[]="Automation event fan-out protection missing [{$marker}].";

    $inbound=(string)@file_get_contents($root.'/app/Http/Controllers/Public/InboundWebhookController.php');
    foreach(['ConcurrencyGuard','$concurrency->transaction','isUniqueViolation','idempotency','duplicate'] as $marker)if(!str_contains($inbound,$marker))$errors[]="Inbound webhook idempotency protection missing [{$marker}].";

    $newsletterQueue=(string)@file_get_contents($root.'/app/Nexora/Distribution/Services/NewsletterDispatchService.php');
    foreach(['ConcurrencyGuard','lockForUpdate()->findOrFail($campaign->id)',"['sending', 'sent']"] as $marker)if(!str_contains($newsletterQueue,$marker))$errors[]="Newsletter campaign claim missing [{$marker}].";

    $newsletterJob=(string)@file_get_contents($root.'/app/Jobs/SendNewsletterDelivery.php');
    foreach(['function claim(','lockForUpdate()','newsletter_claim_ttl_seconds','message_id','Message-ID','function finishCampaign('] as $marker)if(!str_contains($newsletterJob,$marker))$errors[]="Newsletter delivery concurrency protection missing [{$marker}].";

    $webhookDelivery=(string)@file_get_contents($root.'/app/Nexora/Automation/Services/WebhookDeliveryService.php');
    foreach(['function claim(','lockForUpdate()','webhook_claim_ttl_seconds','attempt_count','Idempotency-Key'] as $marker)if(!str_contains($webhookDelivery,$marker))$errors[]="Outbound webhook claim protection missing [{$marker}].";

    $workflowJob=(string)@file_get_contents($root.'/app/Jobs/ExecuteWorkflowRunJob.php');
    foreach(['function claimRun(','function claimStep(','lockForUpdate()','workflow_claim_ttl_seconds',"->increment('run_count'"] as $marker)if(!str_contains($workflowJob,$marker))$errors[]="Workflow duplicate-execution protection missing [{$marker}].";

    $document=(string)@file_get_contents($root.'/app/Nexora/Documents/Repositories/DatabaseDocumentRepository.php');
    foreach(['lockForUpdate()->findOrFail($document->id)', '$expected !== (int) $locked->lock_version', 'ValidationException::withMessages'] as $marker)if(!str_contains($document,$marker))$errors[]="Document optimistic-lock race protection missing [{$marker}].";

    $revision=(string)@file_get_contents($root.'/app/Nexora/Documents/Services/DocumentRevisionManager.php');
    foreach(['lockForUpdate()->findOrFail($document->id)','$expectedLockVersion','revisions()->max'] as $marker)if(!str_contains($revision,$marker))$errors[]="Document revision serialization missing [{$marker}].";

    $studio=(string)@file_get_contents($root.'/app/Nexora/Studio/Services/StudioManager.php');
    foreach(['ConcurrencyGuard', 'lockForUpdate()->findOrFail($canvas->id)', "lock_version']", 'lock_version'] as $marker)if(!str_contains($studio,$marker))$errors[]="Studio optimistic-lock serialization missing [{$marker}].";

    $currency=(string)@file_get_contents($root.'/app/Nexora/Commerce/Services/CurrencyManager.php');
    if(!str_contains($currency,"->mutex('commerce.currency.default'"))$errors[]='Default-currency invariant must use the portable transaction mutex.';

    $publishing=(string)@file_get_contents($root.'/app/Nexora/Publishing/Services/ArticlePublishingManager.php');
    foreach(['ConcurrencyGuard','publishScheduled()','lockForUpdate()->first()','$locked->status !== \'draft\'','$scheduledAt->isFuture()'] as $marker)if(!str_contains($publishing,$marker))$errors[]="Scheduled publishing serialization missing [{$marker}].";

    $critical=[
        'app/Nexora/Commerce/Services/PaymentService.php',
        'app/Nexora/Commerce/Services/RefundService.php',
        'app/Nexora/Automation/Services/AutomationEventBus.php',
        'app/Nexora/Distribution/Services/NewsletterDispatchService.php',
        'app/Nexora/Documents/Repositories/DatabaseDocumentRepository.php',
        'app/Nexora/Documents/Services/DocumentRevisionManager.php',
        'app/Nexora/Studio/Services/StudioManager.php',
        'app/Nexora/Publishing/Services/ArticlePublishingManager.php',
    ];
    $criticalDirectTransactions=0;
    foreach($critical as $relative){
        $source=(string)@file_get_contents($root.'/'.$relative);
        $criticalDirectTransactions+=substr_count($source,'DB::transaction(');
    }
    if($criticalDirectTransactions!==0)$errors[]="Critical RC19 mutation services must use bounded ConcurrencyGuard transactions; found {$criticalDirectTransactions} direct DB::transaction call(s).";

    return [
        'ok'=>$errors===[],
        'errors'=>array_values(array_unique($errors)),
        'warnings'=>array_values(array_unique($warnings)),
        'metrics'=>[
            'critical_surfaces'=>11,
            'critical_direct_transactions'=>$criticalDirectTransactions,
            'claim_ttls'=>3,
            'portable_mutexes'=>1,
            'external_effect_exactly_once_claims'=>0,
        ],
    ];
}
