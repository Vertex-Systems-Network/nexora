<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Automation;

use App\Http\Controllers\Controller;
use App\Models\WebhookDestination;
use App\Models\WebhookEndpoint;
use App\Models\Workflow;
use App\Nexora\Automation\Services\WebhookUrlPolicy;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class WebhookController extends Controller
{
    public function destination(Request $request, WebhookUrlPolicy $policy, AuditManager $audit): RedirectResponse
    {
        $data = $request->validate(['name'=>['required','string','max:180'],'url'=>['required','url','max:1500'],'timeoutSeconds'=>['required','integer','min:2','max:30'],'maxAttempts'=>['required','integer','min:1','max:8']]);
        $policy->assertAllowed($data['url']);
        $secret = Str::random(64);
        $destination = WebhookDestination::query()->create(['uuid'=>(string)Str::uuid(),'name'=>$data['name'],'url'=>$data['url'],'secret'=>$secret,'enabled'=>true,'timeout_seconds'=>$data['timeoutSeconds'],'max_attempts'=>$data['maxAttempts'],'headers'=>[],'created_by'=>$request->user()?->id]);
        $audit->record('automation.webhook_destination.created',$destination,['url'=>$destination->url],$request);
        return back()->with('success','Outbound webhook destination created.')->with('automation_secret',['kind'=>'Outbound webhook signing secret','name'=>$destination->name,'secret'=>$secret]);
    }

    public function rotateDestination(Request $request, WebhookDestination $destination, AuditManager $audit): RedirectResponse
    {
        $secret=Str::random(64); $destination->forceFill(['secret'=>$secret,'rotated_at'=>now()])->save();
        $audit->record('automation.webhook_destination.rotated',$destination,[],$request);
        return back()->with('success','Outbound webhook signing secret rotated. Update the receiver before the next delivery.')->with('automation_secret',['kind'=>'New outbound webhook signing secret','name'=>$destination->name,'secret'=>$secret]);
    }

    public function toggleDestination(Request $request, WebhookDestination $destination, AuditManager $audit): RedirectResponse
    {
        $data=$request->validate(['enabled'=>['required','boolean']]); $destination->forceFill(['enabled'=>(bool)$data['enabled']])->save();
        $audit->record('automation.webhook_destination.status_changed',$destination,['enabled'=>$destination->enabled],$request); return back()->with('success','Webhook destination updated.');
    }

    public function destroyDestination(Request $request, WebhookDestination $destination, AuditManager $audit): RedirectResponse
    {
        if ($destination->deliveries()->whereIn('status',['queued','sending'])->exists()) return back()->with('error','This destination still has queued deliveries. Disable it and wait for deliveries to finish.');
        $referenced = Workflow::query()->get(['id','name','actions'])->contains(function (Workflow $workflow) use ($destination): bool {
            foreach ((array) $workflow->actions as $action) if (($action['type'] ?? null)==='webhook.send' && (int)data_get($action,'config.destination_id',0)===$destination->id) return true;
            return false;
        });
        if ($referenced) return back()->with('error','This destination is still referenced by a workflow. Remove that action before deleting the destination.');
        $audit->record('automation.webhook_destination.deleted',$destination,['name'=>$destination->name],$request); $destination->delete(); return back()->with('success','Webhook destination deleted.');
    }

    public function endpoint(Request $request, AuditManager $audit): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:180'],'slug'=>['nullable','string','max:180','regex:/^[a-z0-9][a-z0-9-]*$/','unique:nx_webhook_endpoints,slug']]);
        $secret=Str::random(64); $endpoint=WebhookEndpoint::query()->create(['uuid'=>(string)Str::uuid(),'name'=>$data['name'],'slug'=>$data['slug'] ?: Str::slug($data['name']).'-'.Str::lower(Str::random(5)),'secret'=>$secret,'enabled'=>true,'allowed_ips'=>[],'created_by'=>$request->user()?->id]);
        $audit->record('automation.webhook_endpoint.created',$endpoint,[],$request);
        return back()->with('success','Inbound webhook endpoint created. Copy the signing secret now.')->with('automation_secret',['kind'=>'Inbound webhook signing secret','name'=>$endpoint->name,'secret'=>$secret]);
    }

    public function rotate(Request $request, WebhookEndpoint $endpoint, AuditManager $audit): RedirectResponse
    {
        $secret=Str::random(64); $endpoint->forceFill(['previous_secret'=>$endpoint->secret,'previous_secret_valid_until'=>now()->addMinutes(15),'secret'=>$secret,'rotated_at'=>now()])->save();
        $audit->record('automation.webhook_endpoint.rotated',$endpoint,['previous_secret_grace_minutes'=>15],$request);
        return back()->with('success','Webhook secret rotated. Previous secret remains valid for 15 minutes.')->with('automation_secret',['kind'=>'New inbound webhook signing secret','name'=>$endpoint->name,'secret'=>$secret]);
    }

    public function toggleEndpoint(Request $request, WebhookEndpoint $endpoint, AuditManager $audit): RedirectResponse
    {
        $data=$request->validate(['enabled'=>['required','boolean']]); $endpoint->forceFill(['enabled'=>(bool)$data['enabled']])->save();
        $audit->record('automation.webhook_endpoint.status_changed',$endpoint,['enabled'=>$endpoint->enabled],$request); return back()->with('success','Inbound webhook endpoint updated.');
    }

    public function destroyEndpoint(Request $request, WebhookEndpoint $endpoint, AuditManager $audit): RedirectResponse
    {
        $referenced = Workflow::query()->where('trigger_key','webhook.inbound')->get(['id','name','trigger_config'])->contains(fn (Workflow $workflow): bool => (int)data_get((array)$workflow->trigger_config,'endpoint_id',0)===$endpoint->id);
        if ($referenced) return back()->with('error','This inbound endpoint still triggers a workflow. Change or remove that workflow before deleting the endpoint.');
        $audit->record('automation.webhook_endpoint.deleted',$endpoint,['name'=>$endpoint->name],$request); $endpoint->delete(); return back()->with('success','Inbound webhook endpoint deleted.');
    }
}
