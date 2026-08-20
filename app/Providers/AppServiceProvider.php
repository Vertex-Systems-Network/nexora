<?php

declare(strict_types=1);

namespace App\Providers;

use App\Nexora\Admin\Navigation\AdminNavigationRegistry;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;
use App\Nexora\Automation\Services\AutomationEventBus;
use App\Nexora\Automation\Services\AutomationTriggerRegistry;
use App\Nexora\Automation\Services\AutomationActionRegistry;
use App\Nexora\Automation\Services\AutomationDefinitionValidator;
use App\Nexora\Automation\Services\ConditionEvaluator;
use App\Nexora\Automation\Services\WorkflowActionExecutor;
use App\Nexora\Automation\Services\WebhookDeliveryService;
use App\Nexora\Automation\Services\WebhookSigner;
use App\Nexora\Automation\Services\WebhookUrlPolicy;
use App\Observers\AutomationDocumentObserver;
use App\Observers\AutomationMediaObserver;
use App\Observers\AutomationSubscriberObserver;
use App\Observers\AutomationSearchObserver;
use App\Nexora\Cloud\Contracts\ObjectStorageContract;
use App\Nexora\Cloud\Contracts\DistributedLockContract;
use App\Nexora\Cloud\Services\BackupOrchestrator;
use App\Nexora\Cloud\Services\ClusterLeadership;
use App\Nexora\Cloud\Services\HaReadinessService;
use App\Nexora\Cloud\Services\ClusterRehearsalService;
use App\Nexora\Cloud\Services\BackupRestoreRehearsalService;
use App\Nexora\Cloud\Services\HealthProbeService;
use App\Nexora\Cloud\Services\LaravelObjectStorage;
use App\Nexora\Cloud\Services\LaravelDistributedLock;
use App\Nexora\Cloud\Services\NodeIdentity;
use App\Nexora\Cloud\Services\NodeManager;
use App\Nexora\Cloud\Services\RestorePlanner;
use App\Nexora\Cloud\Services\RuntimeLeaseManager;
use App\Nexora\Cloud\Services\RuntimeMetricsRecorder;
use App\Nexora\Cloud\Services\RuntimeTopology;
use App\Nexora\Cloud\Services\RuntimeActivityTracker;
use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use App\Nexora\Cloud\Services\RuntimeActivationIdentity;
use App\Nexora\Cloud\Services\RuntimeEnvironmentIdentity;
use App\Nexora\Cloud\Services\RuntimeEngineIdentity;
use App\Nexora\Cloud\Services\RuntimeStorageDataPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeServiceDataPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeHostClockIdentity;
use App\Nexora\Cloud\Services\RuntimeResourceEnvelopeIdentity;
use App\Nexora\Cloud\Services\RuntimePolicyPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeProcessPlane;
use App\Nexora\Foundation\Network\NetworkDestinationPolicy;
use App\Nexora\Foundation\Network\ApprovedHttpClient;
use App\Nexora\Installation\Database\DatabaseDataPlaneIdentity;
use App\Nexora\Cloud\Services\RuntimeKeyRotationService;
use App\Nexora\Cloud\Services\RuntimeVersionGuard;
use App\Nexora\Commerce\Services\PaymentProviderRegistry;
use App\Nexora\Commerce\Services\CurrencyManager;
use App\Nexora\Commerce\Services\TaxCalculator;
use App\Nexora\Commerce\Services\BillingEventRecorder;
use App\Nexora\Commerce\Services\CommerceOrderService;
use App\Nexora\Commerce\Services\InvoiceService;
use App\Nexora\Commerce\Services\PaymentService;
use App\Nexora\Commerce\Services\RefundService;
use App\Nexora\Commerce\Services\SubscriptionService;
use App\Nexora\Crm\Contracts\CrmCommerceLinkContract;
use App\Nexora\Crm\Contracts\CrmOpportunityManagerContract;
use App\Nexora\Crm\Contracts\CrmTimelineContract;
use App\Nexora\Crm\Services\CrmActivityService;
use App\Nexora\Crm\Services\CrmActivityProviderRegistry;
use App\Nexora\Crm\Services\CrmCommerceLinkService;
use App\Nexora\Crm\Services\CrmCustomFieldService;
use App\Nexora\Crm\Services\CrmLeadConversionService;
use App\Nexora\Crm\Services\CrmOpportunityService;
use App\Nexora\Crm\Services\CrmTimelineService;
use App\Nexora\Membership\Contracts\MembershipAccessContract;
use App\Nexora\Membership\Contracts\MembershipManagerContract;
use App\Nexora\Membership\Services\MembershipAccessManager;
use App\Nexora\Membership\Services\MembershipCommerceSyncService;
use App\Nexora\Membership\Services\MembershipEventRecorder;
use App\Nexora\Membership\Services\MembershipManager;
use App\Nexora\Helpdesk\Contracts\HelpdeskTicketManagerContract;
use App\Nexora\Helpdesk\Services\HelpdeskEventRecorder;
use App\Nexora\Helpdesk\Services\HelpdeskSlaService;
use App\Nexora\Helpdesk\Services\HelpdeskTicketManager;
use App\Observers\MembershipCommerceSubscriptionObserver;
use App\Nexora\Data\ConnectionCatalog;
use App\Nexora\Data\ConnectionTester;
use App\Nexora\Documents\Blocks\BlockRegistry;
use App\Nexora\Documents\Contracts\DocumentRepositoryContract;
use App\Nexora\Documents\Repositories\DatabaseDocumentRepository;
use App\Nexora\Documents\Services\DocumentContentValidator;
use App\Nexora\Documents\Services\DocumentRevisionManager;
use App\Nexora\Documents\Types\DocumentTypeDefinition;
use App\Nexora\Documents\Types\DocumentTypeRegistry;
use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Extensions\Services\ExtensionManifestValidator;
use App\Nexora\Extensions\Services\ExtensionPackageInstaller;
use App\Nexora\Extensions\Services\ExtensionLifecycleManager;
use App\Nexora\Extensions\Services\ExtensionMigrationRunner;
use App\Nexora\Extensions\Services\MarketplaceCatalogService;
use App\Nexora\Extensions\Services\MarketplacePackageStager;
use App\Nexora\Foundation\Runtime\VersionConstraintMatcher;
use App\Nexora\Foundation\Contracts\SettingsContract;
use App\Nexora\Foundation\Settings\DatabaseSettingsRepository;
use App\Nexora\Installation\DatabaseProvisioner;
use App\Nexora\Installation\EnvironmentWriter;
use App\Nexora\Installation\InstallationState;
use App\Nexora\Installation\Installer;
use App\Nexora\Installation\SystemRequirementChecker;
use App\Nexora\Media\Contracts\MediaManagerContract;
use App\Nexora\Media\Services\ImageVariantGenerator;
use App\Nexora\Media\Services\MediaManager;
use App\Nexora\Media\Services\MediaUploadPolicy;
use App\Nexora\Media\Services\MediaUsageManager;
use App\Nexora\Distribution\Services\DistributionAdapterRegistry;
use App\Nexora\Distribution\Services\NewsletterDispatchService;
use App\Nexora\Distribution\Services\NewsletterSubscriptionManager;
use App\Nexora\Distribution\Services\RssFeedService;
use App\Nexora\Discovery\Search\DocumentTextExtractor;
use App\Nexora\Discovery\Search\SearchIndexer;
use App\Nexora\Discovery\Analytics\PrivacyIdentity;
use App\Nexora\Discovery\Analytics\AnalyticsRecorder;
use App\Nexora\Discovery\Analytics\AnalyticsAggregator;
use App\Nexora\Discovery\Crawler\PageInspector;
use App\Nexora\Discovery\Crawler\SeoCrawler;
use App\Observers\DocumentSearchObserver;
use App\Observers\MediaAssetSearchObserver;
use App\Observers\SeoEntrySearchObserver;
use App\Nexora\Security\Audit\AuditManager;
use App\Nexora\Seo\Contracts\SeoRepositoryContract;
use App\Nexora\Seo\Contracts\SeoManagerContract;
use App\Nexora\Seo\Services\DatabaseSeoRepository;
use App\Nexora\Seo\Services\InternalLinkAnalyzer;
use App\Nexora\Seo\Services\SeoAuditService;
use App\Nexora\Seo\Services\SeoMetadataFactory;
use App\Nexora\Seo\Services\SeoManager;
use App\Nexora\Seo\Schema\SchemaGraphBuilder;
use App\Nexora\Seo\Sitemap\SitemapService;
use App\Nexora\Security\Sentinel\Contracts\PackageScannerContract;
use App\Nexora\Security\Sentinel\Scanning\PackageScanner;
use App\Nexora\Security\Sentinel\Support\QuarantineManager;
use App\Nexora\Security\Sentinel\Support\ScanRecorder;
use App\Nexora\Security\SupplyChain\Contracts\SandboxAdapterContract;
use App\Nexora\Security\SupplyChain\Services\PackageContentDigest;
use App\Nexora\Security\SupplyChain\Services\PackageJsonReader;
use App\Nexora\Security\SupplyChain\Services\PolicySandboxAdapter;
use App\Nexora\Security\SupplyChain\Services\ProvenanceService;
use App\Nexora\Security\SupplyChain\Services\SbomService;
use App\Nexora\Security\SupplyChain\Services\SignatureVerifier;
use App\Nexora\Security\SupplyChain\Services\SupplyChainAnalyzer;
use App\Nexora\Themes\Contracts\ThemeManagerContract;
use App\Nexora\Themes\Contracts\ThemeRendererContract;
use App\Nexora\Themes\Services\ThemeManager;
use App\Nexora\Themes\Services\SafeThemeRenderer;
use App\Nexora\Themes\Services\ThemeManifestValidator;
use App\Nexora\Themes\Services\ThemePackageInstaller;
use App\Nexora\Themes\Services\DocumentHtmlRenderer;
use App\Nexora\Studio\Contracts\StudioManagerContract;
use App\Nexora\Studio\Data\StudioElementDefinition;
use App\Nexora\Studio\Services\StudioBindingRegistry;
use App\Nexora\Studio\Services\StudioCanvasRenderer;
use App\Nexora\Studio\Services\StudioCanvasValidator;
use App\Nexora\Studio\Services\StudioElementRegistry;
use App\Nexora\Studio\Services\StudioManager;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledBackgroundTaskFinished;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\Looping;

use App\Nexora\Enterprise\Services\TenantContext;
use App\Nexora\Enterprise\Services\TenantExecutionScope;
use App\Nexora\Enterprise\Services\TenantAuthorizationService;
use App\Nexora\Enterprise\Services\SsoProviderRegistry;
use App\Nexora\Enterprise\Services\EnterpriseAuditRecorder;
use App\Nexora\Enterprise\Services\OrganizationManager;
use App\Nexora\Enterprise\Services\InvitationManager;
use App\Nexora\Enterprise\Services\ScimTokenManager;
use App\Nexora\Enterprise\Services\ImpersonationManager;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AdminNavigationContract::class, AdminNavigationRegistry::class);
        $this->app->singleton(NodeIdentity::class);
        $this->app->singleton(NodeManager::class);
        $this->app->singleton(RuntimeLeaseManager::class);
        $this->app->singleton(RuntimeActivityTracker::class);
        $this->app->singleton(RuntimeDeploymentIdentity::class);
        $this->app->singleton(RuntimeActivationIdentity::class);
        $this->app->singleton(RuntimeEnvironmentIdentity::class);
        $this->app->singleton(RuntimeStorageDataPlaneIdentity::class);
        $this->app->singleton(RuntimeServiceDataPlaneIdentity::class);
        $this->app->singleton(NetworkDestinationPolicy::class);
        $this->app->singleton(ApprovedHttpClient::class);
        $this->app->singleton(RuntimeKeyRotationService::class);
        $this->app->singleton(RuntimeVersionGuard::class);
        $this->app->singleton(ClusterLeadership::class);
        $this->app->singleton(HaReadinessService::class);
        $this->app->singleton(ClusterRehearsalService::class);
        $this->app->singleton(BackupRestoreRehearsalService::class);
        $this->app->bind(ObjectStorageContract::class, LaravelObjectStorage::class);
        $this->app->bind(DistributedLockContract::class, LaravelDistributedLock::class);
        $this->app->singleton(LaravelObjectStorage::class);
        $this->app->singleton(RuntimeTopology::class);
        $this->app->singleton(RuntimeMetricsRecorder::class);
        $this->app->singleton(HealthProbeService::class);
        $this->app->singleton(BackupOrchestrator::class);
        $this->app->singleton(RestorePlanner::class);

        $this->app->singleton(ExtensionManifestValidator::class);
        $this->app->singleton(ExtensionPackageInstaller::class);
        $this->app->singleton(ExtensionLifecycleManager::class);
        $this->app->singleton(ExtensionMigrationRunner::class, fn ($app) => new ExtensionMigrationRunner(
            $app['migrator'],
            $app->make(SandboxAdapterContract::class),
        ));
        $this->app->singleton(MarketplaceCatalogService::class);
        $this->app->singleton(MarketplacePackageStager::class);
        $this->app->singleton(AutomationTriggerRegistry::class);
        $this->app->singleton(AutomationActionRegistry::class);
        $this->app->singleton(ConditionEvaluator::class);
        $this->app->singleton(AutomationDefinitionValidator::class);
        $this->app->singleton(WebhookSigner::class);
        $this->app->singleton(WebhookUrlPolicy::class);
        $this->app->singleton(WorkflowActionExecutor::class);
        $this->app->singleton(WebhookDeliveryService::class);
        $this->app->bind(AutomationEventBusContract::class, AutomationEventBus::class);
        $this->app->singleton(SettingsContract::class, DatabaseSettingsRepository::class);
        $this->app->singleton(AuditManager::class);
        $this->app->singleton(PaymentProviderRegistry::class);
        $this->app->singleton(CurrencyManager::class);
        $this->app->singleton(TaxCalculator::class);
        $this->app->singleton(BillingEventRecorder::class);
        $this->app->singleton(CommerceOrderService::class);
        $this->app->singleton(InvoiceService::class);
        $this->app->singleton(PaymentService::class);
        $this->app->singleton(RefundService::class);
        $this->app->singleton(SubscriptionService::class);
        $this->app->singleton(CrmTimelineService::class);
        $this->app->bind(CrmTimelineContract::class, CrmTimelineService::class);
        $this->app->singleton(CrmActivityService::class);
        $this->app->singleton(CrmActivityProviderRegistry::class);
        $this->app->singleton(CrmOpportunityService::class);
        $this->app->bind(CrmOpportunityManagerContract::class, CrmOpportunityService::class);
        $this->app->singleton(CrmLeadConversionService::class);
        $this->app->singleton(CrmCommerceLinkService::class);
        $this->app->bind(CrmCommerceLinkContract::class, CrmCommerceLinkService::class);
        $this->app->singleton(CrmCustomFieldService::class);
        $this->app->singleton(MembershipEventRecorder::class);
        $this->app->singleton(MembershipManager::class);
        $this->app->bind(MembershipManagerContract::class, MembershipManager::class);
        $this->app->singleton(MembershipAccessManager::class);
        $this->app->bind(MembershipAccessContract::class, MembershipAccessManager::class);
        $this->app->singleton(MembershipCommerceSyncService::class);
        $this->app->singleton(HelpdeskSlaService::class);
        $this->app->singleton(HelpdeskEventRecorder::class);
        $this->app->singleton(HelpdeskTicketManager::class);
        $this->app->bind(HelpdeskTicketManagerContract::class, HelpdeskTicketManager::class);
        $this->app->scoped(TenantContext::class);
        $this->app->scoped(TenantExecutionScope::class);
        $this->app->scoped(TenantAuthorizationService::class);
        $this->app->singleton(SsoProviderRegistry::class);
        $this->app->singleton(EnterpriseAuditRecorder::class);
        $this->app->singleton(OrganizationManager::class);
        $this->app->singleton(InvitationManager::class);
        $this->app->singleton(ScimTokenManager::class);
        $this->app->singleton(ImpersonationManager::class);
        $this->app->singleton(ConnectionCatalog::class);
        $this->app->singleton(ConnectionTester::class);
        $this->app->singleton(MediaUploadPolicy::class);
        $this->app->singleton(ImageVariantGenerator::class);
        $this->app->singleton(MediaUsageManager::class);
        $this->app->bind(MediaManagerContract::class, MediaManager::class);
        $this->app->singleton(DistributionAdapterRegistry::class);
        $this->app->singleton(NewsletterSubscriptionManager::class);
        $this->app->singleton(NewsletterDispatchService::class);
        $this->app->singleton(RssFeedService::class);
        $this->app->singleton(DocumentTextExtractor::class);
        $this->app->singleton(SearchIndexer::class);
        $this->app->singleton(PrivacyIdentity::class);
        $this->app->singleton(AnalyticsRecorder::class);
        $this->app->singleton(AnalyticsAggregator::class);
        $this->app->singleton(PageInspector::class);
        $this->app->singleton(SeoCrawler::class);
        $this->app->singleton(DocumentTypeRegistry::class);
        $this->app->singleton(BlockRegistry::class);
        $this->app->singleton(DocumentRevisionManager::class);
        $this->app->singleton(DocumentContentValidator::class);
        $this->app->bind(DocumentRepositoryContract::class, DatabaseDocumentRepository::class);
        $this->app->bind(SeoRepositoryContract::class, DatabaseSeoRepository::class);
        $this->app->bind(SeoManagerContract::class, SeoManager::class);
        $this->app->singleton(SeoMetadataFactory::class);
        $this->app->singleton(SeoAuditService::class);
        $this->app->singleton(InternalLinkAnalyzer::class);
        $this->app->singleton(SchemaGraphBuilder::class);
        $this->app->singleton(SitemapService::class);
        $this->app->bind(ThemeManagerContract::class, ThemeManager::class);
        $this->app->bind(ThemeRendererContract::class, SafeThemeRenderer::class);
        $this->app->singleton(ThemeManifestValidator::class);
        $this->app->singleton(ThemePackageInstaller::class);
        $this->app->singleton(DocumentHtmlRenderer::class);
        $this->app->singleton(StudioElementRegistry::class);
        $this->app->singleton(StudioBindingRegistry::class);
        $this->app->singleton(StudioCanvasValidator::class);
        $this->app->bind(StudioManagerContract::class, StudioManager::class);
        $this->app->singleton(StudioCanvasRenderer::class);
        $this->app->singleton(PackageScannerContract::class, PackageScanner::class);
        $this->app->singleton(QuarantineManager::class);
        $this->app->singleton(PackageContentDigest::class);
        $this->app->singleton(PackageJsonReader::class);
        $this->app->singleton(SbomService::class);
        $this->app->singleton(SignatureVerifier::class);
        $this->app->singleton(ProvenanceService::class);
        $this->app->singleton(SupplyChainAnalyzer::class);
        $this->app->bind(SandboxAdapterContract::class, PolicySandboxAdapter::class);
        $this->app->singleton(PolicySandboxAdapter::class);
        $this->app->singleton(ScanRecorder::class);
        $this->app->singleton(RuntimeResourceEnvelopeIdentity::class);
        $this->app->singleton(RuntimePolicyPlaneIdentity::class);
        $this->app->singleton(RuntimeProcessPlane::class);
        $this->app->singleton(InstallationState::class);
        $this->app->singleton(SystemRequirementChecker::class);
        $this->app->singleton(EnvironmentWriter::class);
        $this->app->singleton(DatabaseProvisioner::class);
        $this->app->singleton(Installer::class);
    }
    public function boot(DocumentTypeRegistry $types, BlockRegistry $blocks, StudioElementRegistry $studioElements, StudioBindingRegistry $studioBindings): void
    {
        date_default_timezone_set((string)config('app.timezone','UTC'));
        if(class_exists(\Locale::class)) \Locale::setDefault((string)config('app.locale','en'));
        Queue::createPayloadUsing(static function (string $connection,string $queue,array $payload): array {
            $activation=app(RuntimeActivationIdentity::class)->current();$host=app(RuntimeHostClockIdentity::class);return ['nexora'=>['payload_schema'=>max(13,(int)config('nexora-upgrade.queue_payload_schema',13)),'platform_version'=>(string)config('nexora.version'),'deployment_generation'=>app(RuntimeDeploymentIdentity::class)->generation(),'runtime_environment_fingerprint'=>app(RuntimeEnvironmentIdentity::class)->fingerprintValue(),'runtime_engine_fingerprint'=>app(RuntimeEngineIdentity::class)->fingerprintValue(),'runtime_database_fingerprint'=>app(DatabaseDataPlaneIdentity::class)->fingerprintValue(),'runtime_storage_fingerprint'=>app(RuntimeStorageDataPlaneIdentity::class)->fingerprintValue(),'runtime_service_fingerprint'=>app(RuntimeServiceDataPlaneIdentity::class)->fingerprintValue(),'runtime_host_fingerprint'=>$host->fingerprintValue(),'runtime_resource_fingerprint'=>app(RuntimeResourceEnvelopeIdentity::class)->fingerprintValue(),'runtime_policy_fingerprint'=>app(RuntimePolicyPlaneIdentity::class)->fingerprintValue(),'runtime_process_fingerprint'=>app(RuntimeProcessPlane::class)->fingerprintValue(),'activation_epoch'=>$activation['activation_epoch'],'runtime_activation_fingerprint'=>$activation['activation_fingerprint'],'generated_at'=>now()->toIso8601String(),'generated_unix_ms'=>(int)round(microtime(true)*1000)]];
        });
        Queue::before(static function (JobProcessing $event): void {
            app(TenantContext::class)->clear();
            app(RuntimeProcessPlane::class)->heartbeat('queue');
            $nodes=app(NodeManager::class);$nodes->heartbeat();
            if(!$nodes->isReady())throw new \RuntimeException('Queue job start refused because this Nexora node is draining or in maintenance mode.');
            app(RuntimeVersionGuard::class)->assertCompatible();
            $payload=method_exists($event->job,'payload')?(array)$event->job->payload():[];$compat=app(RuntimeVersionGuard::class)->queuePayload($payload);
            if(!$compat['compatible'])throw new \RuntimeException('Queue job start refused: '.(string)$compat['reason']);
            $tracker=app(RuntimeActivityTracker::class);$id=$tracker->queueActivityId($event->job);$tracker->begin('queue',$id,['connection'=>$event->connectionName,'job'=>method_exists($event->job,'resolveName')?(string)$event->job->resolveName():'unknown']);
        });
        Queue::after(static function (JobProcessed $event): void {
            try{$tracker=app(RuntimeActivityTracker::class);$tracker->endActivity('queue',$tracker->queueActivityId($event->job));}catch(\Throwable $e){report($e);}
            try{app(TenantContext::class)->clear();}catch(\Throwable $e){report($e);}if(function_exists('gc_collect_cycles'))gc_collect_cycles();
        });
        Queue::exceptionOccurred(static function (JobExceptionOccurred $event): void {
            try{$tracker=app(RuntimeActivityTracker::class);$tracker->endActivity('queue',$tracker->queueActivityId($event->job));}catch(\Throwable $e){report($e);}
            try{app(TenantContext::class)->clear();}catch(\Throwable $e){report($e);}
        });
        Queue::looping(static function (Looping $event): void {
            try{$key='nexora:runtime:queue-process-heartbeat:'.hash('sha256',app(NodeIdentity::class)->key());if(Cache::add($key,1,now()->addSeconds((int)config('nexora-process-runtime.heartbeat_throttle_seconds',30))))app(RuntimeProcessPlane::class)->heartbeat('queue');}catch(\Throwable $e){report($e);}
            try{app(TenantContext::class)->clear();}catch(\Throwable $e){report($e);}
            try{if(!app(NodeManager::class)->isReady()||!app(RuntimeVersionGuard::class)->compatible())app('queue.worker')->shouldQuit=true;}catch(\Throwable $e){report($e);app('queue.worker')->shouldQuit=true;}
            $restartAt=max(128,(int)config('nexora-runtime.queue.worker_restart_memory_mb',384))*1024*1024;if(memory_get_usage(true)>=$restartAt){try{app('queue.worker')->shouldQuit=true;}catch(\Throwable $e){report($e);}}
        });
        Event::listen(ScheduledTaskStarting::class, static function (ScheduledTaskStarting $event): void {
            app(TenantContext::class)->clear();
            app(RuntimeVersionGuard::class)->assertCompatible();

            $tracker = app(RuntimeActivityTracker::class);
            $tracker->begin('scheduler', $tracker->schedulerActivityId($event->task), [
                'task' => method_exists($event->task, 'getSummaryForDisplay')
                    ? (string) $event->task->getSummaryForDisplay()
                    : 'scheduled-task',
            ]);
        });
        $finishScheduled = static function (object $event): void {
            try {
                $tracker = app(RuntimeActivityTracker::class);
                $tracker->endActivity('scheduler', $tracker->schedulerActivityId($event->task));
            } catch (\Throwable $exception) {
                report($exception);
            } finally {
                app(TenantContext::class)->clear();
            }
        };
        Event::listen(ScheduledTaskFinished::class,$finishScheduled);
        Event::listen(ScheduledBackgroundTaskFinished::class,$finishScheduled);
        Event::listen(ScheduledTaskFailed::class,$finishScheduled);
        \App\Models\Document::observe(DocumentSearchObserver::class);
        \App\Models\Document::observe(AutomationDocumentObserver::class);
        \App\Models\MediaAsset::observe(AutomationMediaObserver::class);
        \App\Models\NewsletterSubscriber::observe(AutomationSubscriberObserver::class);
        \App\Models\SearchQueryLog::observe(AutomationSearchObserver::class);
        \App\Models\MediaAsset::observe(MediaAssetSearchObserver::class);
        \App\Models\SeoEntry::observe(SeoEntrySearchObserver::class);
        \App\Models\CommerceSubscription::observe(MembershipCommerceSubscriptionObserver::class);
        $types->register(new DocumentTypeDefinition('document', 'Document', 'General structured document used as the neutral publishing foundation.', 'file-text'));
        $types->register(new DocumentTypeDefinition('article', 'Article', 'Long-form editorial article with author, taxonomy, series and SEO publishing metadata.', 'newspaper'));
        $types->register(new DocumentTypeDefinition('blog_post', 'Blog post', 'Blog-oriented post with publishing metadata, categories, topics, tags and scheduling.', 'file-text'));

        foreach ([
            ['paragraph', 'Paragraph', 'text'],
            ['heading', 'Heading', 'text'],
            ['list', 'List', 'text'],
            ['quote', 'Quote', 'text'],
            ['code', 'Code', 'technical'],
            ['divider', 'Divider', 'layout'],
            ['image', 'Image', 'media'],
        ] as [$type, $name, $category]) {
            $blocks->register(new \App\Nexora\Documents\Blocks\BlockDefinition($type, $name, $category));
        }

        foreach ([
            new StudioElementDefinition('section', 'Section', 'Layout', 'section', true, ['label' => 'Section'], ['base' => ['padding' => '48px 24px', 'maxWidth' => '1200px', 'margin' => '0 auto'], 'tablet' => [], 'mobile' => ['padding' => '28px 16px']]),
            new StudioElementDefinition('stack', 'Stack', 'Layout', 'stack', true, ['label' => 'Stack', 'direction' => 'vertical'], ['base' => ['gap' => '20px'], 'tablet' => [], 'mobile' => []]),
            new StudioElementDefinition('grid', 'Grid', 'Layout', 'grid', true, ['label' => 'Grid', 'columns' => 2], ['base' => ['gap' => '20px'], 'tablet' => ['columns' => '2'], 'mobile' => ['columns' => '1']]),
            new StudioElementDefinition('heading', 'Heading', 'Content', 'heading', false, ['text' => 'A clear headline', 'level' => 2], ['base' => ['fontSize' => '36px', 'fontWeight' => '700'], 'tablet' => [], 'mobile' => ['fontSize' => '30px']], ['text']),
            new StudioElementDefinition('text', 'Text', 'Content', 'paragraph', false, ['text' => 'Write supporting content here.'], ['base' => ['fontSize' => '16px'], 'tablet' => [], 'mobile' => []], ['text']),
            new StudioElementDefinition('button', 'Button', 'Content', 'button', false, ['text' => 'Learn more', 'href' => '#', 'target' => '_self'], ['base' => ['padding' => '12px 18px', 'borderRadius' => '10px'], 'tablet' => [], 'mobile' => []], ['text']),
            new StudioElementDefinition('divider', 'Divider', 'Content', 'divider', false, [], ['base' => ['margin' => '24px 0'], 'tablet' => [], 'mobile' => []]),
            new StudioElementDefinition('spacer', 'Spacer', 'Layout', 'spacer', false, ['size' => 32], ['base' => [], 'tablet' => [], 'mobile' => []]),
        ] as $definition) {
            $studioElements->register($definition);
        }

        foreach ([
            ['document.title', 'Document title', 'Document', 'Bind text to the current document title.'],
            ['document.excerpt', 'Document excerpt', 'Document', 'Bind text to the current document excerpt.'],
            ['seo.title', 'SEO title', 'SEO', 'Bind text to the canonical SEO title.'],
            ['site.name', 'Site name', 'Platform', 'Bind text to the configured Nexora site name.'],
        ] as [$key, $label, $group, $description]) {
            $studioBindings->register($key, $label, $group, $description);
        }
    }

}
