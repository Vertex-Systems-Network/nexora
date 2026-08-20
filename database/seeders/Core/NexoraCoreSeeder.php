<?php

declare(strict_types=1);

namespace Database\Seeders\Core;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\NewsletterList;
use App\Models\CommerceCurrency;
use App\Models\EnterpriseOrganization;
use App\Models\CrmPipeline;
use App\Models\CrmPipelineStage;
use App\Models\HelpdeskSlaPolicy;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Nexora\Foundation\Runtime\RuntimeSynchronizer;
use App\Nexora\Enterprise\Services\TenantContext;
use Illuminate\Database\Seeder;
use RuntimeException;

final class NexoraCoreSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => 'Full platform control.', 'is_system' => true],
            ['name' => 'Administrator', 'slug' => 'administrator', 'description' => 'Administrative access.', 'is_system' => true],
            ['name' => 'User', 'slug' => 'user', 'description' => 'Standard authenticated user.', 'is_system' => true],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(['slug' => $role['slug']], $role);
        }

        $permissions = [
            ['name' => 'Access Admin', 'slug' => 'admin.access', 'group' => 'admin'],
            ['name' => 'View Users', 'slug' => 'users.view', 'group' => 'users'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'group' => 'users'],
            ['name' => 'Update Users', 'slug' => 'users.update', 'group' => 'users'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'group' => 'users'],
            ['name' => 'View Roles', 'slug' => 'roles.view', 'group' => 'access'],
            ['name' => 'Create Roles', 'slug' => 'roles.create', 'group' => 'access'],
            ['name' => 'Update Roles', 'slug' => 'roles.update', 'group' => 'access'],
            ['name' => 'Delete Roles', 'slug' => 'roles.delete', 'group' => 'access'],
            ['name' => 'View Documents', 'slug' => 'documents.view', 'group' => 'content'],
            ['name' => 'Create Documents', 'slug' => 'documents.create', 'group' => 'content'],
            ['name' => 'Update Documents', 'slug' => 'documents.update', 'group' => 'content'],
            ['name' => 'Delete Documents', 'slug' => 'documents.delete', 'group' => 'content'],
            ['name' => 'View Document Revisions', 'slug' => 'documents.revisions.view', 'group' => 'content'],
            ['name' => 'Restore Document Revisions', 'slug' => 'documents.revisions.restore', 'group' => 'content'],
            ['name' => 'Review Documents', 'slug' => 'documents.review', 'group' => 'content'],
            ['name' => 'View Themes', 'slug' => 'themes.view', 'group' => 'appearance'],
            ['name' => 'Install Themes', 'slug' => 'themes.install', 'group' => 'appearance'],
            ['name' => 'Activate Themes', 'slug' => 'themes.activate', 'group' => 'appearance'],
            ['name' => 'Preview Themes', 'slug' => 'themes.preview', 'group' => 'appearance'],
            ['name' => 'Manage Theme Design Tokens', 'slug' => 'themes.manage', 'group' => 'appearance'],
            ['name' => 'View Studio', 'slug' => 'studio.view', 'group' => 'studio'],
            ['name' => 'Create Studio Canvases', 'slug' => 'studio.create', 'group' => 'studio'],
            ['name' => 'Update Studio Canvases', 'slug' => 'studio.update', 'group' => 'studio'],
            ['name' => 'Publish Studio Canvases', 'slug' => 'studio.publish', 'group' => 'studio'],
            ['name' => 'Delete Studio Canvases', 'slug' => 'studio.delete', 'group' => 'studio'],
            ['name' => 'Manage Studio Components', 'slug' => 'studio.components.manage', 'group' => 'studio'],
            ['name' => 'View SEO', 'slug' => 'seo.view', 'group' => 'seo'],
            ['name' => 'Manage SEO Metadata', 'slug' => 'seo.manage', 'group' => 'seo'],
            ['name' => 'Run SEO Audits', 'slug' => 'seo.audit', 'group' => 'seo'],
            ['name' => 'Manage Schema Graph', 'slug' => 'seo.schema.manage', 'group' => 'seo'],
            ['name' => 'Analyze Internal Links', 'slug' => 'seo.links.analyze', 'group' => 'seo'],
            ['name' => 'View Blog & Articles', 'slug' => 'publishing.view', 'group' => 'publishing'],
            ['name' => 'Manage Article Settings', 'slug' => 'publishing.manage', 'group' => 'publishing'],
            ['name' => 'Manage Publishing Taxonomies', 'slug' => 'publishing.taxonomy.manage', 'group' => 'publishing'],
            ['name' => 'Manage Author Profiles', 'slug' => 'publishing.authors.manage', 'group' => 'publishing'],
            ['name' => 'Manage Content Series', 'slug' => 'publishing.series.manage', 'group' => 'publishing'],
            ['name' => 'View Media Library', 'slug' => 'media.view', 'group' => 'media'],
            ['name' => 'Upload Media', 'slug' => 'media.upload', 'group' => 'media'],
            ['name' => 'Manage Media', 'slug' => 'media.manage', 'group' => 'media'],
            ['name' => 'Move Media to Trash / Restore', 'slug' => 'media.delete', 'group' => 'media'],
            ['name' => 'Permanently Delete Media', 'slug' => 'media.delete.permanent', 'group' => 'media'],
            ['name' => 'View Newsletter & Distribution', 'slug' => 'distribution.view', 'group' => 'distribution'],
            ['name' => 'Manage Newsletter & Distribution', 'slug' => 'distribution.manage', 'group' => 'distribution'],
            ['name' => 'Send Newsletter Campaigns', 'slug' => 'distribution.send', 'group' => 'distribution'],
            ['name' => 'View Search & Analytics', 'slug' => 'discovery.view', 'group' => 'discovery'],
            ['name' => 'Manage Search & Analytics Settings', 'slug' => 'discovery.manage', 'group' => 'discovery'],
            ['name' => 'Rebuild Search Index', 'slug' => 'search.index.manage', 'group' => 'discovery'],
            ['name' => 'View Content Analytics', 'slug' => 'analytics.view', 'group' => 'discovery'],
            ['name' => 'Aggregate Content Analytics', 'slug' => 'analytics.aggregate', 'group' => 'discovery'],
            ['name' => 'View SEO Crawls', 'slug' => 'seo.crawler.view', 'group' => 'seo'],
            ['name' => 'Run SEO Crawler', 'slug' => 'seo.crawler.run', 'group' => 'seo'],
            ['name' => 'View Automation', 'slug' => 'automation.view', 'group' => 'automation'],
            ['name' => 'Manage Workflows', 'slug' => 'automation.manage', 'group' => 'automation'],
            ['name' => 'Run Manual Workflows', 'slug' => 'automation.run', 'group' => 'automation'],
            ['name' => 'Manage Webhooks', 'slug' => 'webhooks.manage', 'group' => 'automation'],
            ['name' => 'View Data Connections', 'slug' => 'data.connections.view', 'group' => 'data'],
            ['name' => 'Manage Data Connections', 'slug' => 'data.connections.manage', 'group' => 'data'],
            ['name' => 'Test Data Connections', 'slug' => 'data.connections.test', 'group' => 'data'],
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'group' => 'settings'],
            ['name' => 'View System Health', 'slug' => 'system.health.view', 'group' => 'system'],
            ['name' => 'View Modules', 'slug' => 'system.modules.view', 'group' => 'system'],
            ['name' => 'View Capabilities', 'slug' => 'system.capabilities.view', 'group' => 'system'],
            ['name' => 'Synchronize Runtime', 'slug' => 'system.runtime.sync', 'group' => 'system'],
            ['name' => 'View Audit Trail', 'slug' => 'audit.view', 'group' => 'security'],
            ['name' => 'View Sentinel', 'slug' => 'security.sentinel.view', 'group' => 'security'],
            ['name' => 'Run Sentinel Scans', 'slug' => 'security.sentinel.scan', 'group' => 'security'],
            ['name' => 'Manage Sentinel Quarantine', 'slug' => 'security.quarantine.manage', 'group' => 'security'],
            ['name' => 'View Supply Chain Security', 'slug' => 'security.supply-chain.view', 'group' => 'security'],
            ['name' => 'Manage Trusted Publishers', 'slug' => 'security.publishers.manage', 'group' => 'security'],
            ['name' => 'View Extensions', 'slug' => 'extensions.view', 'group' => 'extensions'],
            ['name' => 'Install Extensions', 'slug' => 'extensions.install', 'group' => 'extensions'],
            ['name' => 'Manage Extension Lifecycle', 'slug' => 'extensions.manage', 'group' => 'extensions'],
            ['name' => 'Manage Marketplace Sources', 'slug' => 'marketplace.manage', 'group' => 'extensions'],
            ['name' => 'View Commerce', 'slug' => 'commerce.view', 'group' => 'commerce'],
            ['name' => 'Manage Commerce Catalog', 'slug' => 'commerce.catalog.manage', 'group' => 'commerce'],
            ['name' => 'Manage Commerce Customers', 'slug' => 'commerce.customers.manage', 'group' => 'commerce'],
            ['name' => 'Manage Commerce Orders', 'slug' => 'commerce.orders.manage', 'group' => 'commerce'],
            ['name' => 'View Commerce Billing', 'slug' => 'commerce.billing.view', 'group' => 'commerce'],
            ['name' => 'Manage Commerce Billing', 'slug' => 'commerce.billing.manage', 'group' => 'commerce'],
            ['name' => 'Manage Commerce Settings', 'slug' => 'commerce.settings.manage', 'group' => 'commerce'],
            ['name' => 'View CRM', 'slug' => 'crm.view', 'group' => 'crm'],
            ['name' => 'Manage CRM Organizations', 'slug' => 'crm.organizations.manage', 'group' => 'crm'],
            ['name' => 'Manage CRM Contacts', 'slug' => 'crm.contacts.manage', 'group' => 'crm'],
            ['name' => 'Manage CRM Leads', 'slug' => 'crm.leads.manage', 'group' => 'crm'],
            ['name' => 'Manage CRM Opportunities', 'slug' => 'crm.opportunities.manage', 'group' => 'crm'],
            ['name' => 'Manage CRM Activities', 'slug' => 'crm.activities.manage', 'group' => 'crm'],
            ['name' => 'Manage CRM Settings', 'slug' => 'crm.settings.manage', 'group' => 'crm'],
            ['name' => 'Link CRM and Commerce', 'slug' => 'crm.commerce.link', 'group' => 'crm'],
            ['name' => 'View Membership', 'slug' => 'membership.view', 'group' => 'membership'],
            ['name' => 'Manage Membership Plans', 'slug' => 'membership.plans.manage', 'group' => 'membership'],
            ['name' => 'Manage Memberships', 'slug' => 'membership.members.manage', 'group' => 'membership'],
            ['name' => 'Manage Membership Access Policies', 'slug' => 'membership.access.manage', 'group' => 'membership'],
            ['name' => 'View Helpdesk', 'slug' => 'helpdesk.view', 'group' => 'helpdesk'],
            ['name' => 'Manage Helpdesk Tickets', 'slug' => 'helpdesk.tickets.manage', 'group' => 'helpdesk'],
            ['name' => 'Manage Helpdesk Settings', 'slug' => 'helpdesk.settings.manage', 'group' => 'helpdesk'],

            ['name' => 'View Cloud & Operations', 'slug' => 'cloud.operations.view', 'group' => 'cloud'],
            ['name' => 'Manage Runtime Nodes & Metrics', 'slug' => 'cloud.operations.manage', 'group' => 'cloud'],
            ['name' => 'Manage Runtime Backups', 'slug' => 'cloud.backups.manage', 'group' => 'cloud'],
            ['name' => 'View Enterprise', 'slug' => 'enterprise.view', 'group' => 'enterprise'],
            ['name' => 'Manage Organizations', 'slug' => 'enterprise.organizations.manage', 'group' => 'enterprise'],
            ['name' => 'Manage Organization Members', 'slug' => 'enterprise.members.manage', 'group' => 'enterprise'],
            ['name' => 'Manage Enterprise Domains', 'slug' => 'enterprise.domains.manage', 'group' => 'enterprise'],
            ['name' => 'Manage Enterprise Identity', 'slug' => 'enterprise.identity.manage', 'group' => 'enterprise'],
            ['name' => 'Manage SCIM', 'slug' => 'enterprise.scim.manage', 'group' => 'enterprise'],
            ['name' => 'Impersonate Organization Users', 'slug' => 'enterprise.impersonate', 'group' => 'enterprise'],
            ['name' => 'View Enterprise Audit', 'slug' => 'enterprise.audit.view', 'group' => 'enterprise'],
            ['name' => 'Manage Profile', 'slug' => 'profile.manage', 'group' => 'profile'],
            ['name' => 'Manage Sessions', 'slug' => 'sessions.manage', 'group' => 'security'],
            ['name' => 'Use Global Search', 'slug' => 'search.use', 'group' => 'admin'],
            ['name' => 'View Notifications', 'slug' => 'notifications.view', 'group' => 'admin'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(['slug' => $permission['slug']], $permission);
        }

        $allPermissionIds = Permission::query()->pluck('id');
        Role::query()->where('slug', 'super-admin')->first()?->permissions()->sync($allPermissionIds);
        Role::query()->where('slug', 'administrator')->first()?->permissions()->sync($allPermissionIds);

        $userPermissions = Permission::query()->whereIn('slug', ['profile.manage'])->pluck('id');
        Role::query()->where('slug', 'user')->first()?->permissions()->sync($userPermissions);

        $settings = [
            ['group' => 'app', 'key' => 'app.name', 'value' => 'Nexora', 'type' => 'string'],
            ['group' => 'appearance', 'key' => 'appearance.theme', 'value' => 'system', 'type' => 'string'],
            ['group' => 'appearance', 'key' => 'appearance.primary', 'value' => '#7C3AED', 'type' => 'string'],
            ['group' => 'appearance', 'key' => 'appearance.density', 'value' => 'comfortable', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'seo.site_name', 'value' => 'Nexora', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'seo.organization_name', 'value' => '', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'seo.organization_url', 'value' => '', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'seo.organization_logo', 'value' => '', 'type' => 'string'],
            ['group' => 'appearance', 'key' => 'appearance.radius', 'value' => 'medium', 'type' => 'string'],
            ['group' => 'appearance', 'key' => 'appearance.active_theme', 'value' => 'nexora.base', 'type' => 'string'],
            ['group' => 'appearance', 'key' => 'appearance.active_theme_version', 'value' => '1.0.0', 'type' => 'string'],
            ['group' => 'distribution', 'key' => 'distribution.from_name', 'value' => 'Nexora', 'type' => 'string'],
            ['group' => 'distribution', 'key' => 'distribution.public_subscribe', 'value' => true, 'type' => 'boolean'],
            ['group' => 'search', 'key' => 'search.public_enabled', 'value' => true, 'type' => 'boolean'],
            ['group' => 'analytics', 'key' => 'analytics.enabled', 'value' => true, 'type' => 'boolean'],
            ['group' => 'analytics', 'key' => 'analytics.raw_retention_days', 'value' => 90, 'type' => 'integer'],
            ['group' => 'analytics', 'key' => 'analytics.search_retention_days', 'value' => 180, 'type' => 'integer'],
            ['group' => 'seo', 'key' => 'seo.crawler.enabled', 'value' => false, 'type' => 'boolean'],
            ['group' => 'seo', 'key' => 'seo.crawler.max_urls', 'value' => 250, 'type' => 'integer'],
            ['group' => 'automation', 'key' => 'automation.event_retention_days', 'value' => 30, 'type' => 'integer'],
            ['group' => 'automation', 'key' => 'automation.webhook_receipt_retention_days', 'value' => 30, 'type' => 'integer'],
            ['group' => 'cloud', 'key' => 'cloud.metric_retention_days', 'value' => 30, 'type' => 'integer'],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(['key' => $setting['key']], $setting);
        }

        CommerceCurrency::query()->updateOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'symbol' => '$',
                'minor_unit' => 2,
                'enabled' => true,
                'is_default' => true,
            ],
        );

        $this->seedTenantDefaults();
        $this->call(NexoraThemeSeeder::class);

        app(RuntimeSynchronizer::class)->sync();
    }

    private function seedTenantDefaults(): void
    {
        $tenantContext = app(TenantContext::class);

        // Seed commands may run inside a long-lived request or worker. Start from a
        // clean tenant boundary so an organization object from a pre-reset schema
        // cannot be restored after the scoped seed callback finishes.
        $tenantContext->clear();

        $defaultOrganization = EnterpriseOrganization::query()
            ->where('is_default', true)
            ->first();

        if ($defaultOrganization === null) {
            throw new RuntimeException(
                'Nexora core data cannot be seeded because the default enterprise organization does not exist.',
            );
        }

        $tenantContext->runWith($defaultOrganization, function (): void {
            DB::transaction(function (): void {
                $this->seedDefaultCrmPipeline();
                $this->seedDefaultHelpdeskPolicies();
                $this->seedDefaultNewsletterList();
            });
        });
    }

    private function seedDefaultCrmPipeline(): void
    {
        $pipeline = CrmPipeline::query()->updateOrCreate(
            ['slug' => 'sales'],
            [
                'name' => 'Sales Pipeline',
                'is_default' => true,
                'active' => true,
            ],
        );

        $stages = [
            ['slug' => 'new', 'name' => 'New', 'position' => 10, 'probability' => 10, 'is_won' => false, 'is_lost' => false],
            ['slug' => 'qualified', 'name' => 'Qualified', 'position' => 20, 'probability' => 30, 'is_won' => false, 'is_lost' => false],
            ['slug' => 'proposal', 'name' => 'Proposal', 'position' => 30, 'probability' => 60, 'is_won' => false, 'is_lost' => false],
            ['slug' => 'negotiation', 'name' => 'Negotiation', 'position' => 40, 'probability' => 80, 'is_won' => false, 'is_lost' => false],
            ['slug' => 'won', 'name' => 'Won', 'position' => 90, 'probability' => 100, 'is_won' => true, 'is_lost' => false],
            ['slug' => 'lost', 'name' => 'Lost', 'position' => 100, 'probability' => 0, 'is_won' => false, 'is_lost' => true],
        ];

        foreach ($stages as $stage) {
            CrmPipelineStage::query()->updateOrCreate(
                [
                    'pipeline_id' => $pipeline->id,
                    'slug' => $stage['slug'],
                ],
                $stage + ['pipeline_id' => $pipeline->id],
            );
        }
    }

    private function seedDefaultHelpdeskPolicies(): void
    {
        $policies = [
            ['name' => 'Standard', 'priority' => null, 'first_response_minutes' => 480, 'resolution_minutes' => 4320, 'is_default' => true],
            ['name' => 'High priority', 'priority' => 'high', 'first_response_minutes' => 120, 'resolution_minutes' => 1440, 'is_default' => false],
            ['name' => 'Urgent', 'priority' => 'urgent', 'first_response_minutes' => 30, 'resolution_minutes' => 480, 'is_default' => false],
        ];

        foreach ($policies as $policy) {
            HelpdeskSlaPolicy::query()->updateOrCreate(
                ['name' => $policy['name']],
                $policy + [
                    'active' => true,
                    'business_hours' => null,
                ],
            );
        }
    }

    private function seedDefaultNewsletterList(): void
    {
        $list = NewsletterList::query()->firstOrNew([
            'slug' => 'general-updates',
        ]);

        if (! $list->exists) {
            $list->uuid = (string) Str::uuid();
        }

        $list->fill([
            'name' => 'General updates',
            'description' => 'Default audience for public Nexora newsletter subscriptions.',
            'status' => 'active',
            'metadata' => [],
        ]);
        $list->save();
    }
}
