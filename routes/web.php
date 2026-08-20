<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\Automation\AutomationController;
use App\Http\Controllers\Admin\Automation\WebhookController;
use App\Http\Controllers\Public\InboundWebhookController;
use App\Http\Controllers\Admin\Appearance\ThemeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Extensions\ExtensionController;
use App\Http\Controllers\Admin\Commerce\CommerceDashboardController;
use App\Http\Controllers\Admin\Commerce\ProductController as CommerceProductController;
use App\Http\Controllers\Admin\Commerce\CustomerController as CommerceCustomerController;
use App\Http\Controllers\Admin\Commerce\OrderController as CommerceOrderController;
use App\Http\Controllers\Admin\Commerce\BillingController as CommerceBillingController;
use App\Http\Controllers\Admin\Commerce\CommerceSettingsController;
use App\Http\Controllers\Admin\Crm\CrmDashboardController;
use App\Http\Controllers\Admin\Crm\OrganizationController as CrmOrganizationController;
use App\Http\Controllers\Admin\Crm\ContactController as CrmContactController;
use App\Http\Controllers\Admin\Crm\LeadController as CrmLeadController;
use App\Http\Controllers\Admin\Crm\OpportunityController as CrmOpportunityController;
use App\Http\Controllers\Admin\Crm\ActivityController as CrmActivityController;
use App\Http\Controllers\Admin\Crm\CrmSettingsController;
use App\Http\Controllers\Admin\Crm\CommerceLinkController as CrmCommerceLinkController;

use App\Http\Controllers\Admin\Membership\MembershipDashboardController;
use App\Http\Controllers\Admin\Membership\MembershipPlanController;
use App\Http\Controllers\Admin\Membership\MembershipController as AdminMembershipController;
use App\Http\Controllers\Admin\Membership\MembershipAccessPolicyController;
use App\Http\Controllers\Admin\Helpdesk\HelpdeskDashboardController;
use App\Http\Controllers\Admin\Helpdesk\HelpdeskTicketController;
use App\Http\Controllers\Admin\Helpdesk\HelpdeskSettingsController;
use App\Http\Controllers\Admin\Enterprise\EnterpriseController;
use App\Http\Controllers\Admin\Cloud\CloudOperationsController;
use App\Http\Controllers\Operations\RuntimeHealthController;
use App\Http\Controllers\Enterprise\InvitationController as EnterpriseInvitationController;
use App\Http\Controllers\Enterprise\ScimController;
use App\Http\Controllers\Enterprise\SsoController;


use App\Http\Controllers\Admin\Discovery\DiscoveryController;
use App\Http\Controllers\Public\SiteSearchController;
use App\Http\Middleware\RecordPublicAnalytics;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveEnterpriseOrganization;
use App\Http\Middleware\RuntimeNodeHeartbeat;
use App\Http\Middleware\EnsureTenantRouteBinding;
use App\Http\Controllers\Admin\Content\DocumentController;
use App\Http\Controllers\Admin\Content\DocumentAutosaveController;
use App\Http\Controllers\Admin\Content\DocumentRevisionController;
use App\Http\Controllers\Admin\Content\DocumentReviewController;
use App\Http\Controllers\Admin\Data\DataConnectionController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\Media\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\Distribution\DistributionController;
use App\Http\Controllers\Public\MediaController as PublicMediaController;
use App\Http\Controllers\Public\RssFeedController;
use App\Http\Controllers\Public\NewsletterSubscriptionController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\Publishing\ArticleController;
use App\Http\Controllers\Admin\Publishing\AuthorProfileController;
use App\Http\Controllers\Admin\Publishing\SeriesController;
use App\Http\Controllers\Admin\Publishing\TaxonomyController;
use App\Http\Controllers\Public\BlogController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\Seo\SeoDashboardController;
use App\Http\Controllers\Admin\Seo\DocumentSeoController;
use App\Http\Controllers\Admin\Seo\InternalLinkController;
use App\Http\Controllers\Admin\Seo\SeoSettingsController;
use App\Http\Controllers\Public\SitemapController;
use App\Http\Controllers\Public\ThemePageController;
use App\Http\Controllers\Public\ThemePreviewController;
use App\Http\Controllers\Admin\Security\SentinelController;
use App\Http\Controllers\Admin\Security\SupplyChainController;
use App\Http\Controllers\Admin\SavedViewController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\Studio\StudioController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\System\CapabilityRuntimeController;
use App\Http\Controllers\Admin\System\ModuleRuntimeController;
use App\Http\Controllers\Admin\System\RuntimeSyncController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Install\InstallerController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;


Route::post('/locale', LocaleController::class)->middleware('throttle:30,1')->name('locale.update');

Route::prefix('install')
    ->name('install.')
    ->withoutMiddleware([RuntimeNodeHeartbeat::class, ResolveEnterpriseOrganization::class, HandleInertiaRequests::class])
    ->group(function (): void {
    Route::get('/', [InstallerController::class, 'index'])->middleware('throttle:60,1')->name('index');
    Route::get('/source-status', [InstallerController::class, 'sourceStatus'])->middleware('throttle:120,1')->name('source.status');
    Route::get('/runtime-handoff', [InstallerController::class, 'runtimeHandoff'])->middleware('throttle:60,1')->name('runtime.handoff');
    Route::post('/database/test', [InstallerController::class, 'testDatabase'])->middleware('throttle:30,1')->name('database.test');
    Route::post('/data-service/test', [InstallerController::class, 'testDataService'])->middleware('throttle:60,1')->name('data-service.test');
    Route::post('/database/backup/stream', [InstallerController::class, 'backupDatabase'])->middleware('throttle:30,1')->name('database.backup.stream');
    Route::get('/database/backup/{token}', [InstallerController::class, 'downloadBackup'])->middleware('throttle:30,1')->where('token', '[a-f0-9]{48}')->name('database.backup.download');
    Route::post('/stream', [InstallerController::class, 'stream'])->middleware('throttle:8,10')->name('stream');
    Route::post('/cancel', [InstallerController::class, 'cancel'])->middleware('throttle:30,1')->name('cancel');
    Route::post('/status', [InstallerController::class, 'status'])->middleware('throttle:60,1')->name('status');
    Route::post('/', [InstallerController::class, 'store'])->middleware('throttle:8,10')->name('store');
});

Route::get('/health/live', [RuntimeHealthController::class, 'live'])->middleware('throttle:120,1')->name('runtime.health.live');
Route::get('/health/ready', [RuntimeHealthController::class, 'ready'])->middleware('throttle:120,1')->name('runtime.health.ready');
Route::get('/sitemap.xml', SitemapController::class)->middleware('throttle:60,1')->name('sitemap');
Route::get('/feed.xml', RssFeedController::class)->middleware('throttle:60,1')->name('feed');
Route::get('/media/{asset:uuid}/{variant?}', PublicMediaController::class)->where('variant', '[0-9]+')->name('media.public');
Route::post('/newsletter/subscribe', [NewsletterSubscriptionController::class, 'subscribe'])->middleware('throttle:10,1')->name('newsletter.subscribe');
Route::post('/hooks/{endpoint:uuid}', InboundWebhookController::class)->middleware('throttle:120,1')->name('webhooks.inbound');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterSubscriptionController::class, 'confirm'])->where('token','[a-f0-9]{64}')->name('newsletter.unsubscribe.confirm');
Route::post('/newsletter/unsubscribe/{token}', [NewsletterSubscriptionController::class, 'unsubscribe'])->where('token','[a-f0-9]{64}')->middleware('throttle:10,1')->name('newsletter.unsubscribe');

Route::get('/', [ThemePageController::class, 'home'])->middleware(RecordPublicAnalytics::class)->name('home');
Route::get('/content/{document:slug}', [ThemePageController::class, 'document'])->middleware(RecordPublicAnalytics::class)->name('content.show');
Route::get('/search', SiteSearchController::class)->middleware([RecordPublicAnalytics::class, 'throttle:60,1'])->name('site.search');

Route::get('/sso/{organization:slug}/{provider}', [SsoController::class, 'start'])->middleware('throttle:30,1')->name('enterprise.sso.start');
Route::match(['get','post'], '/sso/{organization:slug}/{provider}/callback', [SsoController::class, 'callback'])->middleware('throttle:60,1')->name('enterprise.sso.callback');
Route::get('/scim/v2/Users', [ScimController::class, 'users'])->middleware('throttle:120,1')->name('enterprise.scim.users');
Route::post('/scim/v2/Users', [ScimController::class, 'createUser'])->middleware('throttle:60,1')->name('enterprise.scim.users.create');
Route::patch('/scim/v2/Users/{user}', [ScimController::class, 'patchUser'])->middleware('throttle:120,1')->name('enterprise.scim.users.patch');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/enterprise/invitations/{token}/accept', [EnterpriseInvitationController::class, 'accept'])->middleware('throttle:20,1')->name('enterprise.invitation.accept');
    Route::get('/theme-preview/{token}', ThemePreviewController::class)->where('token', '[a-f0-9]{64}')->name('theme.preview.public');
    Route::get('/verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1')->name('verification.send');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin', EnsureTenantRouteBinding::class])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
    Route::put('/users/bulk', [UserController::class, 'bulk'])->middleware('permission:users.update')->name('users.bulk');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.update')->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');

    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:roles.view')->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])->middleware('permission:roles.create')->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.create')->name('roles.store');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:roles.update')->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.update')->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete')->name('roles.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->middleware('permission:profile.manage')->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->middleware('permission:profile.manage')->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->middleware('permission:profile.manage')->name('profile.password');
    Route::delete('/profile/sessions', [ProfileController::class, 'destroyOtherSessions'])->middleware('permission:sessions.manage')->name('profile.sessions.destroy');

    Route::get('/audit', AuditLogController::class)->middleware('permission:audit.view')->name('audit.index');
    Route::get('/notifications', [NotificationController::class, 'index'])->middleware('permission:notifications.view')->name('notifications.index');
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'read'])->middleware('permission:notifications.view')->name('notifications.read');
    Route::put('/notifications/read-all', [NotificationController::class, 'readAll'])->middleware('permission:notifications.view')->name('notifications.read-all');
    Route::get('/search', SearchController::class)->middleware(['permission:search.use', 'throttle:60,1'])->name('search');
    Route::get('/discovery', [DiscoveryController::class, 'index'])->middleware('permission:discovery.view')->name('discovery.index');
    Route::post('/discovery/reindex', [DiscoveryController::class, 'reindex'])->middleware(['permission:search.index.manage', 'throttle:6,1'])->name('discovery.reindex');
    Route::post('/discovery/aggregate', [DiscoveryController::class, 'aggregate'])->middleware(['permission:analytics.aggregate', 'throttle:12,1'])->name('discovery.aggregate');
    Route::put('/discovery/settings', [DiscoveryController::class, 'settings'])->middleware('permission:discovery.manage')->name('discovery.settings');
    Route::post('/discovery/crawl', [DiscoveryController::class, 'crawl'])->middleware(['permission:seo.crawler.run', 'throttle:3,1'])->name('discovery.crawl');
    Route::post('/discovery/crawls/{run}/cancel', [DiscoveryController::class, 'cancel'])->middleware(['permission:seo.crawler.run', 'throttle:6,1'])->name('discovery.crawl.cancel');
    Route::get('/discovery/crawls/{run}', [DiscoveryController::class, 'show'])->middleware('permission:seo.crawler.view')->name('discovery.crawl.show');
    Route::get('/saved-views', [SavedViewController::class, 'index'])->name('saved-views.index');
    Route::post('/saved-views', [SavedViewController::class, 'store'])->name('saved-views.store');
    Route::delete('/saved-views/{savedView}', [SavedViewController::class, 'destroy'])->name('saved-views.destroy');

    Route::get('/documents', [DocumentController::class, 'index'])->middleware('permission:documents.view')->name('documents.index');
    Route::get('/documents/create', [DocumentController::class, 'create'])->middleware('permission:documents.create')->name('documents.create');
    Route::post('/documents', [DocumentController::class, 'store'])->middleware('permission:documents.create')->name('documents.store');
    Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->middleware('permission:documents.update')->name('documents.edit');
    Route::put('/documents/{document}', [DocumentController::class, 'update'])->middleware('permission:documents.update')->name('documents.update');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->middleware('permission:documents.delete')->name('documents.destroy');
    Route::put('/documents/{document}/autosave', DocumentAutosaveController::class)->middleware(['permission:documents.update', 'throttle:90,1'])->name('documents.autosave');
    Route::get('/documents/{document}/revisions', [DocumentRevisionController::class, 'index'])->middleware('permission:documents.revisions.view')->name('documents.revisions.index');
    Route::post('/documents/{document}/revisions/{revision}/restore', [DocumentRevisionController::class, 'restore'])->middleware('permission:documents.revisions.restore')->name('documents.revisions.restore');
    Route::post('/documents/{document}/review-comments', [DocumentReviewController::class, 'store'])->middleware('permission:documents.review')->name('documents.review-comments.store');
    Route::patch('/documents/{document}/review-comments/{comment}/resolve', [DocumentReviewController::class, 'resolve'])->middleware('permission:documents.review')->name('documents.review-comments.resolve');


    Route::get('/media', [AdminMediaController::class, 'index'])->middleware('permission:media.view')->name('media.index');
    Route::post('/media/upload', [AdminMediaController::class, 'upload'])->middleware(['permission:media.upload', 'throttle:30,1'])->name('media.upload');
    Route::put('/media/{asset}', [AdminMediaController::class, 'update'])->middleware('permission:media.manage')->name('media.update');
    Route::delete('/media/{asset}', [AdminMediaController::class, 'destroy'])->middleware('permission:media.delete')->name('media.destroy');
    Route::post('/media/{asset}/restore', [AdminMediaController::class, 'restore'])->middleware('permission:media.delete')->name('media.restore');
    Route::delete('/media/{asset}/force', [AdminMediaController::class, 'forceDelete'])->middleware('permission:media.delete.permanent')->name('media.force-delete');
    Route::post('/media/folders', [AdminMediaController::class, 'folder'])->middleware('permission:media.manage')->name('media.folders.store');
    Route::post('/media/collections', [AdminMediaController::class, 'collection'])->middleware('permission:media.manage')->name('media.collections.store');
    Route::post('/media/collections/{collection}/assets', [AdminMediaController::class, 'collectionAsset'])->middleware('permission:media.manage')->name('media.collections.assets.store');

    Route::get('/distribution', [DistributionController::class, 'index'])->middleware('permission:distribution.view')->name('distribution.index');
    Route::post('/distribution/lists', [DistributionController::class, 'createList'])->middleware('permission:distribution.manage')->name('distribution.lists.store');
    Route::post('/distribution/subscribers', [DistributionController::class, 'subscriber'])->middleware('permission:distribution.manage')->name('distribution.subscribers.store');
    Route::patch('/distribution/subscribers/{subscriber}', [DistributionController::class, 'subscriberStatus'])->middleware('permission:distribution.manage')->name('distribution.subscribers.update');
    Route::post('/distribution/campaigns', [DistributionController::class, 'campaign'])->middleware('permission:distribution.manage')->name('distribution.campaigns.store');
    Route::post('/distribution/campaigns/{campaign}/queue', [DistributionController::class, 'queue'])->middleware(['permission:distribution.send', 'throttle:10,1'])->name('distribution.campaigns.queue');


    Route::get('/publishing/articles', [ArticleController::class, 'index'])->middleware('permission:publishing.view')->name('publishing.articles.index');
    Route::get('/publishing/articles/{document}/settings', [ArticleController::class, 'edit'])->middleware('permission:publishing.view')->name('publishing.articles.edit');
    Route::put('/publishing/articles/{document}/settings', [ArticleController::class, 'update'])->middleware('permission:publishing.manage')->name('publishing.articles.update');
    Route::get('/publishing/taxonomy', [TaxonomyController::class, 'index'])->middleware('permission:publishing.view')->name('publishing.taxonomy.index');
    Route::post('/publishing/taxonomy', [TaxonomyController::class, 'store'])->middleware('permission:publishing.taxonomy.manage')->name('publishing.taxonomy.store');
    Route::put('/publishing/taxonomy/{term}', [TaxonomyController::class, 'update'])->middleware('permission:publishing.taxonomy.manage')->name('publishing.taxonomy.update');
    Route::delete('/publishing/taxonomy/{term}', [TaxonomyController::class, 'destroy'])->middleware('permission:publishing.taxonomy.manage')->name('publishing.taxonomy.destroy');
    Route::get('/publishing/authors', [AuthorProfileController::class, 'index'])->middleware('permission:publishing.view')->name('publishing.authors.index');
    Route::post('/publishing/authors', [AuthorProfileController::class, 'store'])->middleware('permission:publishing.authors.manage')->name('publishing.authors.store');
    Route::put('/publishing/authors/{author}', [AuthorProfileController::class, 'update'])->middleware('permission:publishing.authors.manage')->name('publishing.authors.update');
    Route::delete('/publishing/authors/{author}', [AuthorProfileController::class, 'destroy'])->middleware('permission:publishing.authors.manage')->name('publishing.authors.destroy');
    Route::get('/publishing/series', [SeriesController::class, 'index'])->middleware('permission:publishing.view')->name('publishing.series.index');
    Route::post('/publishing/series', [SeriesController::class, 'store'])->middleware('permission:publishing.series.manage')->name('publishing.series.store');
    Route::put('/publishing/series/{series}', [SeriesController::class, 'update'])->middleware('permission:publishing.series.manage')->name('publishing.series.update');
    Route::delete('/publishing/series/{series}', [SeriesController::class, 'destroy'])->middleware('permission:publishing.series.manage')->name('publishing.series.destroy');

    Route::get('/seo', SeoDashboardController::class)->middleware('permission:seo.view')->name('seo.index');
    Route::get('/seo/settings', [SeoSettingsController::class, 'edit'])->middleware('permission:seo.manage')->name('seo.settings.edit');
    Route::put('/seo/settings', [SeoSettingsController::class, 'update'])->middleware('permission:seo.manage')->name('seo.settings.update');
    Route::get('/seo/documents/{document}', [DocumentSeoController::class, 'edit'])->middleware('permission:seo.view')->name('seo.documents.edit');
    Route::put('/seo/documents/{document}', [DocumentSeoController::class, 'update'])->middleware('permission:seo.manage')->name('seo.documents.update');
    Route::post('/seo/documents/{document}/internal-links/refresh', [InternalLinkController::class, 'refresh'])->middleware(['permission:seo.links.analyze', 'throttle:20,1'])->name('seo.internal-links.refresh');
    Route::patch('/seo/internal-links/{suggestion}', [InternalLinkController::class, 'update'])->middleware('permission:seo.manage')->name('seo.internal-links.update');

    Route::get('/data/connections', [DataConnectionController::class, 'index'])->middleware('permission:data.connections.view')->name('data.connections.index');
    Route::post('/data/connections', [DataConnectionController::class, 'store'])->middleware('permission:data.connections.manage')->name('data.connections.store');
    Route::post('/data/connections/{connection}/test', [DataConnectionController::class, 'test'])->middleware(['permission:data.connections.test', 'throttle:30,1'])->name('data.connections.test');
    Route::patch('/data/connections/{connection}', [DataConnectionController::class, 'toggle'])->middleware('permission:data.connections.manage')->name('data.connections.toggle');
    Route::delete('/data/connections/{connection}', [DataConnectionController::class, 'destroy'])->middleware('permission:data.connections.manage')->name('data.connections.destroy');

    Route::get('/appearance/themes', [ThemeController::class, 'index'])->middleware('permission:themes.view')->name('themes.index');
    Route::post('/appearance/themes/install', [ThemeController::class, 'install'])->middleware(['permission:themes.install', 'throttle:6,10'])->name('themes.install');
    Route::post('/appearance/themes/versions/{version}/activate', [ThemeController::class, 'activate'])->middleware('permission:themes.activate')->name('themes.activate');
    Route::post('/appearance/themes/versions/{version}/preview', [ThemeController::class, 'preview'])->middleware(['permission:themes.preview', 'throttle:20,1'])->name('themes.preview');
    Route::post('/appearance/themes/rollback', [ThemeController::class, 'rollback'])->middleware('permission:themes.activate')->name('themes.rollback');
    Route::put('/appearance/themes/{theme}/tokens', [ThemeController::class, 'updateTokens'])->middleware('permission:themes.manage')->name('themes.tokens.update');

    Route::get('/studio', [StudioController::class, 'index'])->middleware('permission:studio.view')->name('studio.index');
    Route::post('/studio', [StudioController::class, 'store'])->middleware('permission:studio.create')->name('studio.store');
    Route::get('/studio/{canvas}/edit', [StudioController::class, 'edit'])->middleware('permission:studio.view')->name('studio.edit');
    Route::put('/studio/{canvas}', [StudioController::class, 'update'])->middleware('permission:studio.update')->name('studio.update');
    Route::post('/studio/{canvas}/publish', [StudioController::class, 'publish'])->middleware('permission:studio.publish')->name('studio.publish');
    Route::post('/studio/{canvas}/unpublish', [StudioController::class, 'unpublish'])->middleware('permission:studio.publish')->name('studio.unpublish');
    Route::post('/studio/{canvas}/components', [StudioController::class, 'component'])->middleware('permission:studio.components.manage')->name('studio.components.store');
    Route::delete('/studio/{canvas}', [StudioController::class, 'destroy'])->middleware('permission:studio.delete')->name('studio.destroy');

    Route::get('/automation', [AutomationController::class, 'index'])->middleware('permission:automation.view')->name('automation.index');
    Route::get('/automation/create', [AutomationController::class, 'create'])->middleware('permission:automation.manage')->name('automation.create');
    Route::post('/automation', [AutomationController::class, 'store'])->middleware('permission:automation.manage')->name('automation.store');
    Route::get('/automation/runs/{run}', [AutomationController::class, 'showRun'])->middleware('permission:automation.view')->name('automation.runs.show');
    Route::get('/automation/{workflow}/edit', [AutomationController::class, 'edit'])->middleware('permission:automation.manage')->name('automation.edit');
    Route::put('/automation/{workflow}', [AutomationController::class, 'update'])->middleware('permission:automation.manage')->name('automation.update');
    Route::patch('/automation/{workflow}/status', [AutomationController::class, 'toggle'])->middleware('permission:automation.manage')->name('automation.status');
    Route::post('/automation/{workflow}/run', [AutomationController::class, 'manual'])->middleware(['permission:automation.run','throttle:30,1'])->name('automation.run');
    Route::delete('/automation/{workflow}', [AutomationController::class, 'destroy'])->middleware('permission:automation.manage')->name('automation.destroy');
    Route::post('/automation/webhooks/destinations', [WebhookController::class, 'destination'])->middleware('permission:webhooks.manage')->name('automation.webhooks.destinations.store');
    Route::post('/automation/webhooks/destinations/{destination}/rotate', [WebhookController::class, 'rotateDestination'])->middleware(['permission:webhooks.manage','throttle:20,1'])->name('automation.webhooks.destinations.rotate');
    Route::patch('/automation/webhooks/destinations/{destination}', [WebhookController::class, 'toggleDestination'])->middleware('permission:webhooks.manage')->name('automation.webhooks.destinations.toggle');
    Route::delete('/automation/webhooks/destinations/{destination}', [WebhookController::class, 'destroyDestination'])->middleware('permission:webhooks.manage')->name('automation.webhooks.destinations.destroy');
    Route::post('/automation/webhooks/endpoints', [WebhookController::class, 'endpoint'])->middleware('permission:webhooks.manage')->name('automation.webhooks.endpoints.store');
    Route::post('/automation/webhooks/endpoints/{endpoint}/rotate', [WebhookController::class, 'rotate'])->middleware(['permission:webhooks.manage','throttle:20,1'])->name('automation.webhooks.endpoints.rotate');
    Route::patch('/automation/webhooks/endpoints/{endpoint}', [WebhookController::class, 'toggleEndpoint'])->middleware('permission:webhooks.manage')->name('automation.webhooks.endpoints.toggle');
    Route::delete('/automation/webhooks/endpoints/{endpoint}', [WebhookController::class, 'destroyEndpoint'])->middleware('permission:webhooks.manage')->name('automation.webhooks.endpoints.destroy');


    Route::get('/extensions', [ExtensionController::class, 'index'])->middleware('permission:extensions.view')->name('extensions.index');
    Route::get('/extensions/{extension}', [ExtensionController::class, 'show'])->middleware('permission:extensions.view')->name('extensions.show');
    Route::post('/extensions/install/{artifact}', [ExtensionController::class, 'install'])->middleware(['permission:extensions.install','throttle:12,1'])->name('extensions.install');
    Route::put('/extensions/{extension}/capabilities', [ExtensionController::class, 'capabilities'])->middleware('permission:extensions.manage')->name('extensions.capabilities');
    Route::post('/extensions/{extension}/enable', [ExtensionController::class, 'enable'])->middleware('permission:extensions.manage')->name('extensions.enable');
    Route::post('/extensions/{extension}/disable', [ExtensionController::class, 'disable'])->middleware('permission:extensions.manage')->name('extensions.disable');
    Route::post('/extensions/{extension}/rollback', [ExtensionController::class, 'rollback'])->middleware('permission:extensions.manage')->name('extensions.rollback');
    Route::delete('/extensions/{extension}', [ExtensionController::class, 'uninstall'])->middleware('permission:extensions.manage')->name('extensions.uninstall');
    Route::post('/extensions/marketplace/sources', [ExtensionController::class, 'source'])->middleware('permission:marketplace.manage')->name('extensions.marketplace.sources');
    Route::post('/extensions/marketplace/sources/{source}/sync', [ExtensionController::class, 'sync'])->middleware(['permission:marketplace.manage','throttle:12,1'])->name('extensions.marketplace.sync');
    Route::post('/extensions/marketplace/items/{item}/stage', [ExtensionController::class, 'stage'])->middleware(['permission:extensions.install','throttle:8,1'])->name('extensions.marketplace.stage');

    Route::get('/commerce', CommerceDashboardController::class)->middleware('permission:commerce.view')->name('commerce.index');
    Route::get('/commerce/products', [CommerceProductController::class, 'index'])->middleware('permission:commerce.view')->name('commerce.products');
    Route::post('/commerce/products', [CommerceProductController::class, 'store'])->middleware('permission:commerce.catalog.manage')->name('commerce.products.store');
    Route::post('/commerce/products/{product}/prices', [CommerceProductController::class, 'price'])->middleware('permission:commerce.catalog.manage')->name('commerce.products.prices.store');
    Route::patch('/commerce/products/{product}/status', [CommerceProductController::class, 'status'])->middleware('permission:commerce.catalog.manage')->name('commerce.products.status');
    Route::get('/commerce/customers', [CommerceCustomerController::class, 'index'])->middleware('permission:commerce.view')->name('commerce.customers');
    Route::post('/commerce/customers', [CommerceCustomerController::class, 'store'])->middleware('permission:commerce.customers.manage')->name('commerce.customers.store');
    Route::get('/commerce/orders', [CommerceOrderController::class, 'index'])->middleware('permission:commerce.view')->name('commerce.orders');
    Route::post('/commerce/orders', [CommerceOrderController::class, 'store'])->middleware('permission:commerce.orders.manage')->name('commerce.orders.store');
    Route::post('/commerce/orders/{order}/place', [CommerceOrderController::class, 'place'])->middleware('permission:commerce.orders.manage')->name('commerce.orders.place');
    Route::post('/commerce/orders/{order}/invoice', [CommerceOrderController::class, 'invoice'])->middleware('permission:commerce.billing.manage')->name('commerce.orders.invoice');
    Route::get('/commerce/billing', [CommerceBillingController::class, 'index'])->middleware('permission:commerce.billing.view')->name('commerce.billing');
    Route::get('/commerce/settings', [CommerceSettingsController::class, 'index'])->middleware('permission:commerce.settings.manage')->name('commerce.settings');
    Route::post('/commerce/settings/currencies', [CommerceSettingsController::class, 'currency'])->middleware('permission:commerce.settings.manage')->name('commerce.settings.currencies');
    Route::post('/commerce/settings/tax-rates', [CommerceSettingsController::class, 'tax'])->middleware('permission:commerce.settings.manage')->name('commerce.settings.tax');
    Route::post('/commerce/settings/providers', [CommerceSettingsController::class, 'provider'])->middleware('permission:commerce.settings.manage')->name('commerce.settings.providers');
    Route::post('/commerce/settings/providers/{config}/health', [CommerceSettingsController::class, 'health'])->middleware(['permission:commerce.settings.manage','throttle:30,1'])->name('commerce.settings.providers.health');

    Route::get('/crm', CrmDashboardController::class)->middleware('permission:crm.view')->name('crm.index');
    Route::get('/crm/organizations', [CrmOrganizationController::class, 'index'])->middleware('permission:crm.view')->name('crm.organizations');
    Route::post('/crm/organizations', [CrmOrganizationController::class, 'store'])->middleware('permission:crm.organizations.manage')->name('crm.organizations.store');
    Route::get('/crm/organizations/{organization}', [CrmOrganizationController::class, 'show'])->middleware('permission:crm.view')->name('crm.organizations.show');
    Route::get('/crm/contacts', [CrmContactController::class, 'index'])->middleware('permission:crm.view')->name('crm.contacts');
    Route::post('/crm/contacts', [CrmContactController::class, 'store'])->middleware('permission:crm.contacts.manage')->name('crm.contacts.store');
    Route::get('/crm/contacts/{contact}', [CrmContactController::class, 'show'])->middleware('permission:crm.view')->name('crm.contacts.show');
    Route::get('/crm/leads', [CrmLeadController::class, 'index'])->middleware('permission:crm.view')->name('crm.leads');
    Route::post('/crm/leads', [CrmLeadController::class, 'store'])->middleware('permission:crm.leads.manage')->name('crm.leads.store');
    Route::post('/crm/leads/{lead}/convert', [CrmLeadController::class, 'convert'])->middleware('permission:crm.leads.manage')->name('crm.leads.convert');
    Route::get('/crm/opportunities', [CrmOpportunityController::class, 'index'])->middleware('permission:crm.view')->name('crm.opportunities');
    Route::post('/crm/opportunities', [CrmOpportunityController::class, 'store'])->middleware('permission:crm.opportunities.manage')->name('crm.opportunities.store');
    Route::get('/crm/opportunities/{opportunity}', [CrmOpportunityController::class, 'show'])->middleware('permission:crm.view')->name('crm.opportunities.show');
    Route::patch('/crm/opportunities/{opportunity}/stage', [CrmOpportunityController::class, 'stage'])->middleware('permission:crm.opportunities.manage')->name('crm.opportunities.stage');
    Route::post('/crm/activities', [CrmActivityController::class, 'store'])->middleware('permission:crm.activities.manage')->name('crm.activities.store');
    Route::post('/crm/notes', [CrmActivityController::class, 'note'])->middleware('permission:crm.activities.manage')->name('crm.notes.store');
    Route::get('/crm/commerce-links', [CrmCommerceLinkController::class, 'index'])->middleware('permission:crm.commerce.link')->name('crm.commerce-links');
    Route::post('/crm/commerce-links', [CrmCommerceLinkController::class, 'store'])->middleware('permission:crm.commerce.link')->name('crm.commerce-links.store');
    Route::get('/crm/settings', [CrmSettingsController::class, 'index'])->middleware('permission:crm.settings.manage')->name('crm.settings');
    Route::post('/crm/settings/pipelines', [CrmSettingsController::class, 'pipeline'])->middleware('permission:crm.settings.manage')->name('crm.settings.pipelines');
    Route::post('/crm/settings/pipelines/{pipeline}/stages', [CrmSettingsController::class, 'stage'])->middleware('permission:crm.settings.manage')->name('crm.settings.stages');
    Route::post('/crm/settings/custom-fields', [CrmSettingsController::class, 'customField'])->middleware('permission:crm.settings.manage')->name('crm.settings.custom-fields');


    Route::get('/membership', MembershipDashboardController::class)->middleware('permission:membership.view')->name('membership.index');
    Route::get('/membership/plans', [MembershipPlanController::class, 'index'])->middleware('permission:membership.view')->name('membership.plans');
    Route::post('/membership/plans', [MembershipPlanController::class, 'store'])->middleware('permission:membership.plans.manage')->name('membership.plans.store');
    Route::post('/membership/plans/{plan}/entitlements', [MembershipPlanController::class, 'entitlement'])->middleware('permission:membership.plans.manage')->name('membership.plans.entitlements.store');
    Route::get('/membership/members', [AdminMembershipController::class, 'index'])->middleware('permission:membership.view')->name('membership.members');
    Route::post('/membership/members', [AdminMembershipController::class, 'store'])->middleware('permission:membership.members.manage')->name('membership.members.store');
    Route::patch('/membership/members/{membership}/status', [AdminMembershipController::class, 'status'])->middleware('permission:membership.members.manage')->name('membership.members.status');
    Route::get('/membership/access-policies', [MembershipAccessPolicyController::class, 'index'])->middleware('permission:membership.view')->name('membership.access-policies');
    Route::post('/membership/access-policies', [MembershipAccessPolicyController::class, 'store'])->middleware('permission:membership.access.manage')->name('membership.access-policies.store');

    Route::get('/helpdesk', HelpdeskDashboardController::class)->middleware('permission:helpdesk.view')->name('helpdesk.index');
    Route::get('/helpdesk/tickets', [HelpdeskTicketController::class, 'index'])->middleware('permission:helpdesk.view')->name('helpdesk.tickets');
    Route::post('/helpdesk/tickets', [HelpdeskTicketController::class, 'store'])->middleware('permission:helpdesk.tickets.manage')->name('helpdesk.tickets.store');
    Route::get('/helpdesk/tickets/{ticket}', [HelpdeskTicketController::class, 'show'])->middleware('permission:helpdesk.view')->name('helpdesk.tickets.show');
    Route::post('/helpdesk/tickets/{ticket}/messages', [HelpdeskTicketController::class, 'message'])->middleware('permission:helpdesk.tickets.manage')->name('helpdesk.tickets.messages.store');
    Route::patch('/helpdesk/tickets/{ticket}/state', [HelpdeskTicketController::class, 'state'])->middleware('permission:helpdesk.tickets.manage')->name('helpdesk.tickets.state');
    Route::get('/helpdesk/settings', [HelpdeskSettingsController::class, 'index'])->middleware('permission:helpdesk.settings.manage')->name('helpdesk.settings');
    Route::post('/helpdesk/settings/sla', [HelpdeskSettingsController::class, 'sla'])->middleware('permission:helpdesk.settings.manage')->name('helpdesk.settings.sla.store');

    Route::get('/enterprise', [EnterpriseController::class, 'index'])->middleware('permission:enterprise.view')->name('enterprise.index');
    Route::post('/enterprise/organizations', [EnterpriseController::class, 'store'])->middleware('permission:enterprise.organizations.manage')->name('enterprise.organizations.store');
    Route::post('/enterprise/switch', [EnterpriseController::class, 'switch'])->middleware('permission:enterprise.view')->name('enterprise.switch');
    Route::get('/enterprise/organizations/{organization}', [EnterpriseController::class, 'show'])->middleware('permission:enterprise.view')->name('enterprise.organizations.show');
    Route::post('/enterprise/organizations/{organization}/members', [EnterpriseController::class, 'member'])->middleware('permission:enterprise.members.manage')->name('enterprise.members.store');
    Route::put('/enterprise/organizations/{organization}/roles/{role}', [EnterpriseController::class, 'role'])->middleware('permission:enterprise.members.manage')->name('enterprise.roles.update');
    Route::post('/enterprise/organizations/{organization}/invitations', [EnterpriseController::class, 'invite'])->middleware('permission:enterprise.members.manage')->name('enterprise.invitations.store');
    Route::post('/enterprise/organizations/{organization}/domains', [EnterpriseController::class, 'domain'])->middleware('permission:enterprise.domains.manage')->name('enterprise.domains.store');
    Route::post('/enterprise/organizations/{organization}/domains/{domain}/verify', [EnterpriseController::class, 'verifyDomain'])->middleware(['permission:enterprise.domains.manage','throttle:10,1'])->name('enterprise.domains.verify');
    Route::post('/enterprise/organizations/{organization}/sso', [EnterpriseController::class, 'sso'])->middleware('permission:enterprise.identity.manage')->name('enterprise.sso.store');
    Route::post('/enterprise/organizations/{organization}/sso/{provider}/health', [EnterpriseController::class, 'ssoHealth'])->middleware(['permission:enterprise.identity.manage','throttle:20,1'])->name('enterprise.sso.health');
    Route::post('/enterprise/organizations/{organization}/scim', [EnterpriseController::class, 'scim'])->middleware('permission:enterprise.scim.manage')->name('enterprise.scim.store');
    Route::patch('/enterprise/organizations/{organization}/scim/{token}/revoke', [EnterpriseController::class, 'revokeScim'])->middleware('permission:enterprise.scim.manage')->name('enterprise.scim.revoke');
    Route::post('/enterprise/organizations/{organization}/impersonate', [EnterpriseController::class, 'impersonate'])->middleware(['permission:enterprise.impersonate','throttle:10,1'])->name('enterprise.impersonate');
    Route::post('/enterprise/impersonation/stop', [EnterpriseController::class, 'stopImpersonation'])->name('enterprise.impersonation.stop');

    Route::get('/cloud', [CloudOperationsController::class, 'index'])->middleware('permission:cloud.operations.view')->name('cloud.index');
    Route::post('/cloud/node/heartbeat', [CloudOperationsController::class, 'heartbeat'])->middleware(['permission:cloud.operations.manage','throttle:30,1'])->name('cloud.node.heartbeat');
    Route::post('/cloud/node/status', [CloudOperationsController::class, 'status'])->middleware(['permission:cloud.operations.manage','throttle:20,1'])->name('cloud.node.status');
    Route::post('/cloud/metrics', [CloudOperationsController::class, 'metrics'])->middleware(['permission:cloud.operations.manage','throttle:20,1'])->name('cloud.metrics');
    Route::post('/cloud/backups', [CloudOperationsController::class, 'backup'])->middleware(['permission:cloud.backups.manage','throttle:3,5'])->name('cloud.backups.create');
    Route::post('/cloud/backups/{backup}/verify', [CloudOperationsController::class, 'verify'])->middleware(['permission:cloud.backups.manage','throttle:10,1'])->name('cloud.backups.verify');
    Route::post('/cloud/backups/{backup}/restore-plan', [CloudOperationsController::class, 'restorePlan'])->middleware(['permission:cloud.backups.manage','throttle:5,5'])->name('cloud.backups.restore-plan');
    Route::get('/cloud/backups/{backup}/download', [CloudOperationsController::class, 'download'])->middleware(['permission:cloud.backups.manage','throttle:20,1'])->name('cloud.backups.download');

    Route::get('/settings', [SettingsController::class, 'edit'])->middleware('permission:settings.manage')->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->middleware('permission:settings.manage')->name('settings.update');
    Route::get('/system/health', SystemHealthController::class)->middleware('permission:system.health.view')->name('system.health');
    Route::get('/system/modules', ModuleRuntimeController::class)->middleware('permission:system.modules.view')->name('system.modules');
    Route::get('/system/capabilities', CapabilityRuntimeController::class)->middleware('permission:system.capabilities.view')->name('system.capabilities');
    Route::post('/system/runtime/sync', RuntimeSyncController::class)->middleware(['permission:system.runtime.sync', 'throttle:10,1'])->name('system.runtime.sync');

    Route::get('/security/sentinel', [SentinelController::class, 'index'])->middleware('permission:security.sentinel.view')->name('security.sentinel.index');
    Route::post('/security/sentinel', [SentinelController::class, 'store'])->middleware(['permission:security.sentinel.scan', 'throttle:10,1'])->name('security.sentinel.store');
    Route::get('/security/sentinel/scans/{scan}', [SentinelController::class, 'show'])->middleware('permission:security.sentinel.view')->name('security.sentinel.show');
    Route::post('/security/sentinel/packages/{package}/rescan', [SentinelController::class, 'rescan'])->middleware(['permission:security.sentinel.scan', 'throttle:10,1'])->name('security.sentinel.rescan');
    Route::delete('/security/sentinel/packages/{package}', [SentinelController::class, 'destroy'])->middleware('permission:security.quarantine.manage')->name('security.sentinel.destroy');
    Route::get('/security/supply-chain', [SupplyChainController::class, 'index'])->middleware('permission:security.supply-chain.view')->name('security.supply-chain.index');
    Route::post('/security/supply-chain/publishers', [SupplyChainController::class, 'storePublisher'])->middleware(['permission:security.publishers.manage','throttle:20,1'])->name('security.supply-chain.publishers.store');
    Route::patch('/security/supply-chain/publishers/{publisher}/revoke', [SupplyChainController::class, 'revokePublisher'])->middleware('permission:security.publishers.manage')->name('security.supply-chain.publishers.revoke');
});


Route::get('/blog', [BlogController::class, 'index'])->middleware(RecordPublicAnalytics::class)->name('blog.index');
Route::get('/blog/category/{term:slug}', [BlogController::class, 'category'])->middleware(RecordPublicAnalytics::class)->name('blog.category');
Route::get('/blog/topic/{term:slug}', [BlogController::class, 'topic'])->middleware(RecordPublicAnalytics::class)->name('blog.topic');
Route::get('/blog/tag/{term:slug}', [BlogController::class, 'tag'])->middleware(RecordPublicAnalytics::class)->name('blog.tag');
Route::get('/blog/series/{series:slug}', [BlogController::class, 'series'])->middleware(RecordPublicAnalytics::class)->name('blog.series');
Route::get('/authors/{author:slug}', [BlogController::class, 'author'])->middleware(RecordPublicAnalytics::class)->name('authors.show');
Route::get('/blog/{document:slug}', [ThemePageController::class, 'document'])->middleware(RecordPublicAnalytics::class)->name('blog.show');
Route::get('/articles/{document:slug}', [ThemePageController::class, 'document'])->middleware(RecordPublicAnalytics::class)->name('articles.show');

Route::get('/{path}', [ThemePageController::class, 'resolve'])->where('path', '.*')->middleware(RecordPublicAnalytics::class)->name('content.resolve');
