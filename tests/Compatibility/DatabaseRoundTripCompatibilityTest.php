<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use App\Models\CrmPipelineStage;
use App\Models\EnterpriseOrganization;
use App\Models\HelpdeskSlaPolicy;
use App\Models\NewsletterList;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Foundation\Database\ConcurrencyGuard;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

final class DatabaseRoundTripCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_are_repeatable_and_all_tenant_roots_have_tenant_columns(): void
    {
        $this->seed(DatabaseSeeder::class);
        $before=$this->seedCounts();
        $this->seed(DatabaseSeeder::class);
        self::assertSame($before,$this->seedCounts(),'Repeated DatabaseSeeder execution must not add duplicate deterministic records.');

        $root=dirname(__DIR__,2);
        $migration=(string)file_get_contents($root.'/database/migrations/2026_08_16_002000_add_nexora_enterprise_tenancy.php');
        self::assertMatchesRegularExpression('/private array \\$tenantTables\\s*=\\s*\\[(.*?)\\];/s',$migration);
        preg_match('/private array \\$tenantTables\\s*=\\s*\\[(.*?)\\];/s',$migration,$match);
        preg_match_all('/[\'\"]([^\'\"]+)[\'\"]/',(string)($match[1]??''),$tables);
        $tenantTables=array_values(array_unique($tables[1]??[]));
        self::assertCount(51,$tenantTables);
        foreach($tenantTables as $table){
            self::assertTrue(Schema::hasTable($table),"Tenant table {$table} must exist.");
            self::assertTrue(Schema::hasColumn($table,'tenant_id'),"Tenant table {$table} must contain tenant_id.");
            self::assertSame(0,DB::table($table)->whereNull('tenant_id')->count(),"Tenant table {$table} contains orphan rows without tenant_id.");
        }

        self::assertSame(1,EnterpriseOrganization::query()->where('is_default',true)->count(),'Exactly one default enterprise organization is required after a fresh migration.');
    }


    public function test_nullable_unique_keys_accept_multiple_nulls_on_every_supported_driver(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant=(string)EnterpriseOrganization::query()->where('is_default',true)->value('id');
        self::assertNotSame('', $tenant);
        $now=now();

        for($index=1;$index<=2;$index++){
            DB::table('nx_commerce_products')->insert([
                'id'=>(string)Str::uuid(),'sku'=>null,'name'=>'Nullable SKU '.$index,'slug'=>'nullable-sku-'.$index.'-'.Str::lower(Str::random(5)),
                'type'=>'product','status'=>'draft','metadata'=>json_encode([],JSON_THROW_ON_ERROR),'tenant_id'=>$tenant,'created_at'=>$now,'updated_at'=>$now,
            ]);
            DB::table('nx_commerce_payment_transactions')->insert([
                'id'=>(string)Str::uuid(),'provider_key'=>'compat','type'=>'payment','status'=>'pending','currency'=>'USD','amount_minor'=>100,
                'idempotency_key'=>null,'metadata'=>json_encode([],JSON_THROW_ON_ERROR),'tenant_id'=>$tenant,'created_at'=>$now,'updated_at'=>$now,
            ]);
            DB::table('nx_commerce_refunds')->insert([
                'id'=>(string)Str::uuid(),'status'=>'pending','currency'=>'USD','amount_minor'=>1,'idempotency_key'=>null,
                'metadata'=>json_encode([],JSON_THROW_ON_ERROR),'tenant_id'=>$tenant,'created_at'=>$now,'updated_at'=>$now,
            ]);
            DB::table('nx_supply_chain_artifacts')->insert([
                'id'=>(string)Str::uuid(),'scan_id'=>null,'artifact_sha256'=>hash('sha256','artifact-'.$index),'content_sha256'=>hash('sha256','content-'.$index),
                'signature_status'=>'missing','provenance_status'=>'missing','trust_tier'=>'untrusted','sandbox_profile'=>'deny-execution','created_at'=>$now,'updated_at'=>$now,
            ]);
            DB::table('nx_automation_events')->insert([
                'uuid'=>(string)Str::uuid(),'event_key'=>'compat.nullable','idempotency_key'=>null,'payload'=>json_encode(['index'=>$index],JSON_THROW_ON_ERROR),
                'occurred_at'=>$now,'tenant_id'=>$tenant,'created_at'=>$now,'updated_at'=>$now,
            ]);
            $planId=(string)Str::uuid();
            DB::table('nx_membership_plans')->insert([
                'id'=>$planId,'name'=>'Nullable Plan '.$index,'slug'=>'nullable-plan-'.$index.'-'.Str::lower(Str::random(5)),'status'=>'active',
                'commerce_price_id'=>null,'metadata'=>json_encode([],JSON_THROW_ON_ERROR),'tenant_id'=>$tenant,'created_at'=>$now,'updated_at'=>$now,
            ]);
            DB::table('nx_memberships')->insert([
                'id'=>(string)Str::uuid(),'plan_id'=>$planId,'commerce_subscription_id'=>null,'status'=>'active','tenant_id'=>$tenant,'created_at'=>$now,'updated_at'=>$now,
            ]);
        }

        self::assertGreaterThanOrEqual(2,DB::table('nx_commerce_products')->whereNull('sku')->count());
        self::assertGreaterThanOrEqual(2,DB::table('nx_commerce_payment_transactions')->whereNull('idempotency_key')->count());
        self::assertGreaterThanOrEqual(2,DB::table('nx_commerce_refunds')->whereNull('idempotency_key')->count());
        self::assertGreaterThanOrEqual(2,DB::table('nx_supply_chain_artifacts')->whereNull('scan_id')->count());
        self::assertGreaterThanOrEqual(2,DB::table('nx_automation_events')->whereNull('idempotency_key')->count());
        self::assertGreaterThanOrEqual(2,DB::table('nx_membership_plans')->whereNull('commerce_price_id')->count());
        self::assertGreaterThanOrEqual(2,DB::table('nx_memberships')->whereNull('commerce_subscription_id')->count());
    }


    public function test_portable_transaction_mutex_is_available_on_every_supported_driver(): void
    {
        $guard=app(ConcurrencyGuard::class);
        $value=$guard->mutex('compatibility.database-round-trip', static fn (): string => 'claimed');
        self::assertSame('claimed',$value);
        self::assertSame(1,DB::table('nx_concurrency_mutexes')->where('name','compatibility.database-round-trip')->count());
    }

    /** @return array<string,int> */
    private function seedCounts(): array
    {
        return [
            'roles'=>Role::query()->count(),
            'permissions'=>Permission::query()->count(),
            'sla'=>HelpdeskSlaPolicy::query()->count(),
            'crm_stages'=>CrmPipelineStage::query()->count(),
            'newsletter_lists'=>NewsletterList::query()->count(),
            'demo_users'=>User::query()->where('email','like','demo-user-%@nexora.test')->count(),
        ];
    }
}
