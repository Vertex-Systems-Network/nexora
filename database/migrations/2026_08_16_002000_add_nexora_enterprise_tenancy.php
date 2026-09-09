<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    /** @var list<string> */
    private array $tenantTables = [
        'nx_documents','nx_media_assets','nx_newsletter_lists','nx_workflows','nx_studio_canvases','nx_data_connections',
        'nx_commerce_products','nx_commerce_customers','nx_commerce_orders','nx_commerce_invoices','nx_commerce_payment_transactions','nx_commerce_refunds','nx_commerce_subscriptions',
        'nx_crm_organizations','nx_crm_contacts','nx_crm_pipelines','nx_crm_leads','nx_crm_opportunities','nx_crm_activities','nx_crm_notes','nx_crm_custom_field_definitions',
        'nx_membership_plans','nx_memberships','nx_membership_access_policies','nx_helpdesk_sla_policies','nx_helpdesk_tickets',
        'nx_automation_events','nx_workflow_runs','nx_webhook_destinations','nx_webhook_endpoints',
        'nx_search_index','nx_search_query_logs','nx_analytics_events','nx_analytics_daily_metrics','nx_crawl_runs',
        'nx_seo_entries','nx_seo_schema_nodes','nx_theme_settings','nx_theme_activations',
        'nx_media_folders','nx_media_collections','nx_newsletter_subscribers','nx_newsletter_campaigns','nx_newsletter_deliveries','nx_distribution_channels',
        'nx_webhook_deliveries','nx_webhook_receipts','nx_studio_components','nx_taxonomy_terms','nx_author_profiles','nx_content_series','nx_article_metadata',
    ];

    public function up(): void
    {
        Schema::create('nx_enterprise_organizations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 180);
            $table->string('slug', 190)->unique();
            $table->string('status', 24)->default('active')->index();
            $table->boolean('is_default')->default(false)->index();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('timezone', 80)->default('UTC');
            $table->string('locale', 16)->default('en');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('nx_enterprise_roles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name', 120);
            $table->string('slug', 120);
            $table->json('permissions')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->foreign('organization_id', 'nx_ent_role_org_fk')->references('id')->on('nx_enterprise_organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'slug'], 'nx_ent_role_org_slug_uq');
        });

        Schema::create('nx_enterprise_organization_members', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 32)->default('member')->index();
            $table->string('status', 24)->default('active')->index();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
            $table->foreign('organization_id', 'nx_ent_member_org_fk')->references('id')->on('nx_enterprise_organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'user_id'], 'nx_ent_member_org_user_uq');
        });

        Schema::create('nx_enterprise_domains', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('host', 190)->unique();
            $table->string('status', 24)->default('pending')->index();
            $table->boolean('is_primary')->default(false)->index();
            $table->string('verification_token_hash', 64)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->foreign('organization_id', 'nx_ent_domain_org_fk')->references('id')->on('nx_enterprise_organizations')->cascadeOnDelete();
        });

        Schema::create('nx_enterprise_invitations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('email', 255)->index();
            $table->string('role', 32)->default('member');
            $table->string('token_hash', 64)->unique();
            $table->string('status', 24)->default('pending')->index();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->index();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->foreign('organization_id', 'nx_ent_invite_org_fk')->references('id')->on('nx_enterprise_organizations')->cascadeOnDelete();
            $table->index(['organization_id', 'email', 'status'], 'nx_ent_invite_org_email_idx');
        });

        Schema::create('nx_enterprise_sso_providers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name', 160);
            $table->string('slug', 180);
            $table->string('protocol', 24)->index();
            $table->string('adapter_key', 160)->index();
            $table->boolean('enabled')->default(false)->index();
            $table->boolean('enforce_for_members')->default(false)->index();
            $table->json('configuration')->nullable();
            $table->text('secret_payload')->nullable();
            $table->timestamps();
            $table->foreign('organization_id', 'nx_ent_sso_org_fk')->references('id')->on('nx_enterprise_organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'slug'], 'nx_ent_sso_org_slug_uq');
        });

        Schema::create('nx_enterprise_scim_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name', 160);
            $table->string('token_hash', 64)->unique();
            $table->boolean('enabled')->default(true)->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->foreign('organization_id', 'nx_ent_scim_org_fk')->references('id')->on('nx_enterprise_organizations')->cascadeOnDelete();
        });

        Schema::create('nx_enterprise_impersonation_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('target_user_id')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->string('request_hash', 64)->nullable();
            $table->timestamp('started_at')->useCurrent()->index();
            $table->timestamp('ended_at')->nullable()->index();
            $table->timestamps();
            $table->foreign('organization_id', 'nx_ent_imp_org_fk')->references('id')->on('nx_enterprise_organizations')->cascadeOnDelete();
        });

        Schema::create('nx_enterprise_audit_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->nullable()->index();
            $table->string('event_type', 140)->index();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_type', 80)->nullable()->index();
            $table->string('subject_id', 80)->nullable()->index();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->foreign('organization_id', 'nx_ent_audit_org_fk')->references('id')->on('nx_enterprise_organizations')->nullOnDelete();
        });

        Schema::create('nx_enterprise_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('key', 190);
            $table->json('value')->nullable();
            $table->string('type', 32)->default('json');
            $table->timestamps();
            $table->foreign('organization_id', 'nx_ent_setting_org_fk')->references('id')->on('nx_enterprise_organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'key'], 'nx_ent_setting_org_key_uq');
        });

        $defaultId = (string) Str::uuid();
        DB::table('nx_enterprise_organizations')->insert([
            'id' => $defaultId,
            'name' => 'Primary Organization',
            'slug' => 'primary',
            'status' => 'active',
            'is_default' => true,
            'timezone' => 'UTC',
            'locale' => 'en',
            'metadata' => json_encode(['created_by' => 'n0.33-migration'], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            ['name'=>'Owner','slug'=>'owner','permissions'=>['*']],
            ['name'=>'Administrator','slug'=>'admin','permissions'=>['*']],
            ['name'=>'Member','slug'=>'member','permissions'=>['admin.access','profile.manage','documents.view','documents.create','documents.update','media.view','media.upload','publishing.view','crm.view','helpdesk.view','helpdesk.tickets.manage']],
            ['name'=>'Viewer','slug'=>'viewer','permissions'=>['admin.access','profile.manage','documents.view','media.view','publishing.view','crm.view','helpdesk.view']],
        ] as $role) {
            DB::table('nx_enterprise_roles')->insert([
                'id'=>(string) Str::uuid(),'organization_id'=>$defaultId,'name'=>$role['name'],'slug'=>$role['slug'],
                'permissions'=>json_encode($role['permissions'], JSON_THROW_ON_ERROR),'is_system'=>true,'created_at'=>now(),'updated_at'=>now(),
            ]);
        }

        if (Schema::hasTable('nx_roles') && Schema::hasTable('nx_user_roles')) {
            $superAdminId = DB::table('users')->join('nx_user_roles','users.id','=','nx_user_roles.user_id')->join('nx_roles','nx_roles.id','=','nx_user_roles.role_id')->where('nx_roles.slug','super-admin')->value('users.id');
            if ($superAdminId !== null) {
                DB::table('nx_enterprise_organizations')->where('id',$defaultId)->update(['owner_user_id'=>$superAdminId]);
                DB::table('nx_enterprise_organization_members')->insert([
                    'id'=>(string) Str::uuid(),'organization_id'=>$defaultId,'user_id'=>$superAdminId,'role'=>'owner','status'=>'active','joined_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
                ]);
            }
        }

        foreach ($this->tenantTables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'tenant_id')) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->uuid('tenant_id')->nullable()->index('nx_tenant_'.substr(hash('sha256', $tableName), 0, 12).'_idx');
                $table->foreign('tenant_id', 'nx_tenant_'.substr(hash('sha256', $tableName), 0, 12).'_fk')->references('id')->on('nx_enterprise_organizations')->nullOnDelete();
            });
            DB::table($tableName)->whereNull('tenant_id')->update(['tenant_id' => $defaultId]);
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tenantTables) as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                    $table->dropForeign('nx_tenant_'.substr(hash('sha256', $tableName), 0, 12).'_fk');
                    $table->dropIndex('nx_tenant_'.substr(hash('sha256', $tableName), 0, 12).'_idx');
                    $table->dropColumn('tenant_id');
                });
            }
        }
        Schema::dropIfExists('nx_enterprise_settings');
        Schema::dropIfExists('nx_enterprise_audit_events');
        Schema::dropIfExists('nx_enterprise_impersonation_sessions');
        Schema::dropIfExists('nx_enterprise_scim_tokens');
        Schema::dropIfExists('nx_enterprise_sso_providers');
        Schema::dropIfExists('nx_enterprise_invitations');
        Schema::dropIfExists('nx_enterprise_domains');
        Schema::dropIfExists('nx_enterprise_organization_members');
        Schema::dropIfExists('nx_enterprise_roles');
        Schema::dropIfExists('nx_enterprise_organizations');
    }
};
