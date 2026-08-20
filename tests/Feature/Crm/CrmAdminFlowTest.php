<?php

declare(strict_types=1);

namespace Tests\Feature\Crm;

use App\Models\CrmLead;
use App\Models\CrmOpportunity;
use App\Models\CrmOpportunityStageHistory;
use App\Models\CrmPipeline;
use App\Models\CrmPipelineStage;
use App\Models\Role;
use App\Models\User;
use App\Nexora\Crm\Services\CrmLeadConversionService;
use App\Nexora\Crm\Services\CrmOpportunityService;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CrmAdminFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NexoraCoreSeeder::class);
    }

    public function test_administrator_can_open_crm_workspaces(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->roles()->attach(Role::query()->where('slug', 'administrator')->value('id'));

        foreach (['/admin/crm','/admin/crm/organizations','/admin/crm/contacts','/admin/crm/leads','/admin/crm/opportunities','/admin/crm/commerce-links','/admin/crm/settings'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_guest_cannot_open_crm_admin(): void
    {
        $this->get('/admin/crm')->assertRedirect('/login');
    }

    public function test_default_pipeline_is_seeded_and_a_lead_can_be_converted(): void
    {
        $pipeline = CrmPipeline::query()->where('is_default', true)->firstOrFail();
        $stage = CrmPipelineStage::query()->where('pipeline_id', $pipeline->id)->orderBy('position')->firstOrFail();
        $lead = CrmLead::query()->create([
            'title' => 'Enterprise website opportunity',
            'status' => 'new',
            'source' => 'manual',
            'currency' => 'USD',
            'estimated_value_minor' => 125000,
            'score' => 70,
        ]);

        $opportunity = app(CrmLeadConversionService::class)->convert($lead, $pipeline, $stage);

        self::assertSame('converted', $lead->fresh()->status);
        self::assertSame($opportunity->id, $lead->fresh()->converted_opportunity_id);
        self::assertSame(125000, $opportunity->amount_minor);
        self::assertDatabaseHas('nx_crm_opportunity_stage_history', [
            'opportunity_id' => $opportunity->id,
            'to_stage_id' => $stage->id,
        ]);
    }

    public function test_moving_an_opportunity_records_stage_history(): void
    {
        $pipeline = CrmPipeline::query()->where('is_default', true)->firstOrFail();
        $stages = CrmPipelineStage::query()->where('pipeline_id', $pipeline->id)->orderBy('position')->get();
        $from = $stages->firstOrFail();
        $to = $stages->skip(1)->firstOrFail();
        $opportunity = CrmOpportunity::query()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $from->id,
            'name' => 'Move me',
            'status' => 'open',
            'currency' => 'USD',
            'amount_minor' => 50000,
            'probability' => $from->probability,
        ]);

        $moved = app(CrmOpportunityService::class)->moveStage($opportunity, $to);

        self::assertSame($to->id, $moved->stage_id);
        self::assertSame($to->probability, $moved->probability);
        self::assertTrue(CrmOpportunityStageHistory::query()->where('opportunity_id', $opportunity->id)->where('from_stage_id', $from->id)->where('to_stage_id', $to->id)->exists());
    }
}
