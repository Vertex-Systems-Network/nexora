<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const CRM_PIPELINES = 'nx_crm_pipelines';
    private const CRM_PIPELINE_GLOBAL_SLUG = 'nx_crm_pipelines_slug_unique';
    private const CRM_PIPELINE_TENANT_SLUG = 'nx_crm_pipeline_tenant_slug_uq';

    private const CRM_CUSTOM_FIELDS = 'nx_crm_custom_field_definitions';
    private const CRM_CUSTOM_FIELD_GLOBAL_KEY = 'nx_crm_custom_field_key_uq';
    private const CRM_CUSTOM_FIELD_TENANT_KEY = 'nx_crm_custom_field_tenant_key_uq';

    private const MEMBERSHIP_PLANS = 'nx_membership_plans';
    private const MEMBERSHIP_PLAN_GLOBAL_SLUG = 'nx_membership_plans_slug_unique';
    private const MEMBERSHIP_PLAN_TENANT_SLUG = 'nx_membership_plan_tenant_slug_uq';

    private const MEMBERSHIP_POLICIES = 'nx_membership_access_policies';
    private const MEMBERSHIP_POLICY_GLOBAL_RESOURCE = 'nx_membership_access_resource_uq';
    private const MEMBERSHIP_POLICY_TENANT_RESOURCE = 'nx_membership_access_tenant_resource_uq';

    private const CRM_COMMERCE_LINKS = 'nx_crm_commerce_links';
    private const CRM_LINK_GLOBAL_CUSTOMER = 'nx_crm_commerce_links_commerce_customer_id_unique';
    private const CRM_LINK_TENANT_CUSTOMER = 'nx_crm_commerce_tenant_customer_uq';
    private const CRM_LINK_TENANT_INDEX = 'nx_crm_commerce_tenant_idx';
    private const CRM_LINK_TENANT_FOREIGN = 'nx_crm_commerce_tenant_fk';

    public function up(): void
    {
        Schema::table(self::CRM_COMMERCE_LINKS, static function (Blueprint $table): void {
            $table->uuid('tenant_id')->nullable()->index(self::CRM_LINK_TENANT_INDEX);
        });

        foreach (DB::table(self::CRM_COMMERCE_LINKS)->get(['id', 'commerce_customer_id', 'contact_id', 'organization_id']) as $link) {
            $customerTenant = DB::table('nx_commerce_customers')->where('id', $link->commerce_customer_id)->value('tenant_id');
            if (! is_string($customerTenant) || $customerTenant === '') {
                throw new \RuntimeException("CRM Commerce link {$link->id} has no valid Commerce customer tenant.");
            }

            foreach ([
                'contact' => ['table' => 'nx_crm_contacts', 'id' => $link->contact_id],
                'organization' => ['table' => 'nx_crm_organizations', 'id' => $link->organization_id],
            ] as $label => $subject) {
                if ($subject['id'] === null) {
                    continue;
                }

                $subjectTenant = DB::table($subject['table'])->where('id', $subject['id'])->value('tenant_id');
                if (! is_string($subjectTenant) || $subjectTenant === '' || $subjectTenant !== $customerTenant) {
                    throw new \RuntimeException("CRM Commerce link {$link->id} has a cross-tenant {$label} relationship.");
                }
            }

            DB::table(self::CRM_COMMERCE_LINKS)->where('id', $link->id)->update(['tenant_id' => $customerTenant]);
        }

        Schema::table(self::CRM_COMMERCE_LINKS, static function (Blueprint $table): void {
            $table->foreign('tenant_id', self::CRM_LINK_TENANT_FOREIGN)
                ->references('id')
                ->on('nx_enterprise_organizations')
                ->nullOnDelete();
            $table->dropUnique(self::CRM_LINK_GLOBAL_CUSTOMER);
            $table->unique(['tenant_id', 'commerce_customer_id'], self::CRM_LINK_TENANT_CUSTOMER);
        });

        Schema::table(self::CRM_PIPELINES, static function (Blueprint $table): void {
            $table->dropUnique(self::CRM_PIPELINE_GLOBAL_SLUG);
            $table->unique(['tenant_id', 'slug'], self::CRM_PIPELINE_TENANT_SLUG);
        });

        Schema::table(self::CRM_CUSTOM_FIELDS, static function (Blueprint $table): void {
            $table->dropUnique(self::CRM_CUSTOM_FIELD_GLOBAL_KEY);
            $table->unique(['tenant_id', 'entity_type', 'key'], self::CRM_CUSTOM_FIELD_TENANT_KEY);
        });

        Schema::table(self::MEMBERSHIP_PLANS, static function (Blueprint $table): void {
            $table->dropUnique(self::MEMBERSHIP_PLAN_GLOBAL_SLUG);
            $table->unique(['tenant_id', 'slug'], self::MEMBERSHIP_PLAN_TENANT_SLUG);
        });

        Schema::table(self::MEMBERSHIP_POLICIES, static function (Blueprint $table): void {
            $table->dropUnique(self::MEMBERSHIP_POLICY_GLOBAL_RESOURCE);
            $table->unique(['tenant_id', 'resource_type', 'resource_id'], self::MEMBERSHIP_POLICY_TENANT_RESOURCE);
        });
    }

    public function down(): void
    {
        Schema::table(self::MEMBERSHIP_POLICIES, static function (Blueprint $table): void {
            $table->dropUnique(self::MEMBERSHIP_POLICY_TENANT_RESOURCE);
            $table->unique(['resource_type', 'resource_id'], self::MEMBERSHIP_POLICY_GLOBAL_RESOURCE);
        });

        Schema::table(self::MEMBERSHIP_PLANS, static function (Blueprint $table): void {
            $table->dropUnique(self::MEMBERSHIP_PLAN_TENANT_SLUG);
            $table->unique('slug', self::MEMBERSHIP_PLAN_GLOBAL_SLUG);
        });

        Schema::table(self::CRM_CUSTOM_FIELDS, static function (Blueprint $table): void {
            $table->dropUnique(self::CRM_CUSTOM_FIELD_TENANT_KEY);
            $table->unique(['entity_type', 'key'], self::CRM_CUSTOM_FIELD_GLOBAL_KEY);
        });

        Schema::table(self::CRM_PIPELINES, static function (Blueprint $table): void {
            $table->dropUnique(self::CRM_PIPELINE_TENANT_SLUG);
            $table->unique('slug', self::CRM_PIPELINE_GLOBAL_SLUG);
        });

        Schema::table(self::CRM_COMMERCE_LINKS, static function (Blueprint $table): void {
            $table->dropUnique(self::CRM_LINK_TENANT_CUSTOMER);
            $table->unique('commerce_customer_id', self::CRM_LINK_GLOBAL_CUSTOMER);
            $table->dropForeign(self::CRM_LINK_TENANT_FOREIGN);
            $table->dropIndex(self::CRM_LINK_TENANT_INDEX);
            $table->dropColumn('tenant_id');
        });
    }
};
