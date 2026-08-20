<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Automation;

use App\Http\Controllers\Controller;
use App\Jobs\ExecuteWorkflowRunJob;
use App\Models\AutomationEvent;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookDestination;
use App\Models\WebhookEndpoint;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Nexora\Automation\Services\AutomationActionRegistry;
use App\Nexora\Automation\Services\AutomationDefinitionValidator;
use App\Nexora\Automation\Services\AutomationTriggerRegistry;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class AutomationController extends Controller
{
    public function index(Request $request, AutomationTriggerRegistry $triggers, AutomationActionRegistry $actions): Response
    {
        $workflows = Workflow::query()->withCount(['runs'])->latest('updated_at')->paginate(25)->through(fn (Workflow $workflow): array => $this->workflow($workflow));
        $runs = WorkflowRun::query()->with('workflow:id,name')->latest('id')->limit(12)->get()->map(fn (WorkflowRun $run): array => $this->run($run));
        $destinations = WebhookDestination::query()->latest('id')->get()->map(fn (WebhookDestination $destination): array => [
            'id'=>$destination->id,'uuid'=>$destination->uuid,'name'=>$destination->name,'url'=>$destination->url,'enabled'=>$destination->enabled,
            'timeoutSeconds'=>$destination->timeout_seconds,'maxAttempts'=>$destination->max_attempts,'lastDeliveredAt'=>$destination->last_delivered_at?->toIso8601String(),
        ]);
        $endpoints = WebhookEndpoint::query()->latest('id')->get()->map(fn (WebhookEndpoint $endpoint): array => [
            'id'=>$endpoint->id,'uuid'=>$endpoint->uuid,'name'=>$endpoint->name,'slug'=>$endpoint->slug,'enabled'=>$endpoint->enabled,
            'url'=>route('webhooks.inbound',$endpoint,false),'lastReceivedAt'=>$endpoint->last_received_at?->toIso8601String(),'rotatedAt'=>$endpoint->rotated_at?->toIso8601String(),
        ]);
        return Inertia::render('Admin/Automation/Index', [
            'workflows'=>$workflows,'recentRuns'=>$runs,'destinations'=>$destinations,'endpoints'=>$endpoints,
            'triggers'=>array_values($triggers->all()),'actions'=>array_values($actions->all()),
            'users'=>User::query()->where('status','active')->orderBy('name')->get(['id','name','email'])->map(fn (User $user): array => ['value'=>(string)$user->id,'label'=>$user->name,'description'=>$user->email])->values(),
            'newSecret'=>$request->session()->pull('automation_secret'),
        ]);
    }

    public function create(AutomationTriggerRegistry $triggers, AutomationActionRegistry $actions): Response
    {
        return $this->form(null,$triggers,$actions);
    }

    public function store(Request $request, AutomationDefinitionValidator $validator, AuditManager $audit): RedirectResponse
    {
        $base = $request->validate([
            'name'=>['required','string','max:180'],'slug'=>['nullable','string','max:180','regex:/^[a-z0-9][a-z0-9-]*$/','unique:nx_workflows,slug'],
            'description'=>['nullable','string','max:2000'],'status'=>['required','in:draft,active,paused'],
            'trigger_key'=>['required','string','max:120'],'trigger_config'=>['nullable','array'],'conditions'=>['nullable','array','max:20'],'actions'=>['required','array','min:1','max:20'],
        ]);
        $definition = $validator->validate($request->only(['trigger_key','trigger_config','conditions','actions']));
        $workflow = Workflow::query()->create(array_merge($base,$definition,[
            'uuid'=>(string) Str::uuid(),'slug'=>$base['slug'] ?: Str::slug($base['name']).'-'.Str::lower(Str::random(5)),'created_by'=>$request->user()?->id,'updated_by'=>$request->user()?->id,
        ]));
        $audit->record('automation.workflow.created',$workflow,['trigger'=>$workflow->trigger_key],$request);
        return redirect()->route('admin.automation.index')->with('success','Workflow created.');
    }

    public function edit(Workflow $workflow, AutomationTriggerRegistry $triggers, AutomationActionRegistry $actions): Response
    {
        return $this->form($workflow,$triggers,$actions);
    }

    public function update(Request $request, Workflow $workflow, AutomationDefinitionValidator $validator, AuditManager $audit): RedirectResponse
    {
        $base = $request->validate([
            'name'=>['required','string','max:180'],'slug'=>['required','string','max:180','regex:/^[a-z0-9][a-z0-9-]*$/','unique:nx_workflows,slug,'.$workflow->id],
            'description'=>['nullable','string','max:2000'],'status'=>['required','in:draft,active,paused'],
            'trigger_key'=>['required','string','max:120'],'trigger_config'=>['nullable','array'],'conditions'=>['nullable','array','max:20'],'actions'=>['required','array','min:1','max:20'],
        ]);
        $definition = $validator->validate($request->only(['trigger_key','trigger_config','conditions','actions']));
        $workflow->forceFill(array_merge($base,$definition,['updated_by'=>$request->user()?->id]))->save();
        $audit->record('automation.workflow.updated',$workflow,['trigger'=>$workflow->trigger_key],$request);
        return redirect()->route('admin.automation.index')->with('success','Workflow updated.');
    }

    public function toggle(Request $request, Workflow $workflow, AuditManager $audit): RedirectResponse
    {
        $data = $request->validate(['status'=>['required','in:active,paused']]);
        $workflow->forceFill(['status'=>$data['status'],'updated_by'=>$request->user()?->id])->save();
        $audit->record('automation.workflow.status_changed',$workflow,['status'=>$workflow->status],$request);
        return back()->with('success','Workflow status updated.');
    }

    public function manual(Request $request, Workflow $workflow, AuditManager $audit): RedirectResponse
    {
        if ($workflow->trigger_key !== 'manual') return back()->with('error','Only workflows using the Manual run trigger can be started manually.');
        if ($workflow->status !== 'active') return back()->with('error','Activate this workflow before running it.');
        $event = AutomationEvent::query()->create(['uuid'=>(string)Str::uuid(),'event_key'=>'manual','source_type'=>'admin','source_id'=>(string)$request->user()?->id,'payload'=>['manual'=>['user_id'=>$request->user()?->id,'started_at'=>now()->toIso8601String()]],'occurred_at'=>now(),'processed_at'=>now()]);
        $run = WorkflowRun::query()->create(['uuid'=>(string)Str::uuid(),'workflow_id'=>$workflow->id,'automation_event_id'=>$event->id,'status'=>'queued','context'=>$event->payload]);
        ExecuteWorkflowRunJob::dispatch($run->id);
        $audit->record('automation.workflow.manual_queued',$workflow,['run_id'=>$run->id],$request);
        return redirect()->route('admin.automation.runs.show',$run)->with('success','Workflow run queued.');
    }

    public function destroy(Request $request, Workflow $workflow, AuditManager $audit): RedirectResponse
    {
        if ($workflow->status === 'active') return back()->with('error','Pause the workflow before deleting it.');
        $audit->record('automation.workflow.deleted',$workflow,['name'=>$workflow->name],$request);
        $workflow->delete();
        return back()->with('success','Workflow deleted.');
    }

    public function showRun(WorkflowRun $run): Response
    {
        $run->load(['workflow','event','steps']);
        $deliveries = WebhookDelivery::query()->where('workflow_run_id',$run->id)->with('destination:id,name,url')->latest('id')->get()->map(fn (WebhookDelivery $delivery): array => [
            'id'=>$delivery->id,'uuid'=>$delivery->uuid,'destination'=>$delivery->destination?->name,'url'=>$delivery->destination?->url,'status'=>$delivery->status,
            'attempts'=>$delivery->attempt_count,'responseStatus'=>$delivery->response_status,'error'=>$delivery->error,'deliveredAt'=>$delivery->delivered_at?->toIso8601String(),
        ]);
        return Inertia::render('Admin/Automation/Run', ['run'=>$this->run($run,true),'deliveries'=>$deliveries]);
    }

    private function form(?Workflow $workflow, AutomationTriggerRegistry $triggers, AutomationActionRegistry $actions): Response
    {
        return Inertia::render('Admin/Automation/Form', [
            'workflow'=>$workflow ? $this->workflow($workflow) : null,'triggers'=>array_values($triggers->all()),'actions'=>array_values($actions->all()),
            'destinations'=>WebhookDestination::query()->where('enabled',true)->orderBy('name')->get()->map(fn (WebhookDestination $destination): array => ['value'=>(string)$destination->id,'label'=>$destination->name,'description'=>$destination->url])->values(),
            'endpoints'=>WebhookEndpoint::query()->where('enabled',true)->orderBy('name')->get()->map(fn (WebhookEndpoint $endpoint): array => ['value'=>(string)$endpoint->id,'label'=>$endpoint->name,'description'=>$endpoint->slug])->values(),
            'users'=>User::query()->where('status','active')->orderBy('name')->get(['id','name','email'])->map(fn (User $user): array => ['value'=>(string)$user->id,'label'=>$user->name,'description'=>$user->email])->values(),
        ]);
    }

    private function workflow(Workflow $workflow): array
    {
        return ['id'=>$workflow->id,'uuid'=>$workflow->uuid,'name'=>$workflow->name,'slug'=>$workflow->slug,'description'=>$workflow->description,'status'=>$workflow->status,'triggerKey'=>$workflow->trigger_key,
            'triggerConfig'=>$workflow->trigger_config ?? [],'conditions'=>$workflow->conditions ?? [],'actions'=>$workflow->actions ?? [],'runCount'=>$workflow->run_count,'runsCount'=>$workflow->runs_count ?? null,
            'lastRunAt'=>$workflow->last_run_at?->toIso8601String(),'updatedAt'=>$workflow->updated_at?->toIso8601String()];
    }

    private function run(WorkflowRun $run, bool $full=false): array
    {
        $data = ['id'=>$run->id,'uuid'=>$run->uuid,'workflowId'=>$run->workflow_id,'workflowName'=>$run->workflow?->name,'status'=>$run->status,'attempt'=>$run->attempt,'error'=>$run->error,'startedAt'=>$run->started_at?->toIso8601String(),'completedAt'=>$run->completed_at?->toIso8601String(),'createdAt'=>$run->created_at?->toIso8601String()];
        if ($full) $data += ['context'=>$run->context ?? [],'output'=>$run->output ?? [],'event'=>$run->event ? ['key'=>$run->event->event_key,'uuid'=>$run->event->uuid,'payload'=>$run->event->payload] : null,'steps'=>$run->steps->map(fn ($step): array => ['id'=>$step->id,'key'=>$step->step_key,'type'=>$step->action_type,'status'=>$step->status,'attempt'=>$step->attempt,'input'=>$step->input,'output'=>$step->output,'error'=>$step->error,'startedAt'=>$step->started_at?->toIso8601String(),'completedAt'=>$step->completed_at?->toIso8601String()])->values()];
        return $data;
    }
}
