<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Distribution;

use App\Nexora\Enterprise\Validation\TenantExists;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;
use App\Nexora\Distribution\Services\DistributionAdapterRegistry;
use App\Nexora\Distribution\Services\NewsletterDispatchService;
use App\Nexora\Distribution\Services\NewsletterSubscriptionManager;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class DistributionController extends Controller
{
    public function index(DistributionAdapterRegistry $adapters): Response
    {
        $lists = NewsletterList::query()->withCount(['subscribers'=>fn ($q) => $q->where('nx_newsletter_subscribers.status','active')->where('nx_newsletter_list_subscribers.status','subscribed')])->orderBy('name')->get();
        $subscribers = NewsletterSubscriber::query()->latest('id')->limit(50)->get(['id','email','name','status','locale','consented_at','unsubscribed_at']);
        $campaigns = NewsletterCampaign::query()->with(['list:id,name','document:id,title'])->withCount([
            'deliveries as delivered_count'=>fn ($q)=>$q->where('status','sent'),
            'deliveries as failed_count'=>fn ($q)=>$q->where('status','failed'),
        ])->latest('id')->limit(50)->get();
        return Inertia::render('Admin/Distribution/Index', [
            'adapters'=>array_values(array_map(fn ($adapter)=>$adapter->status(), $adapters->all())),
            'lists'=>$lists->map(fn ($list)=>['id'=>$list->id,'name'=>$list->name,'description'=>$list->description,'status'=>$list->status,'subscribers_count'=>$list->subscribers_count])->values(),
            'subscribers'=>$subscribers->map(fn ($subscriber)=>[
                'id'=>$subscriber->id,'email'=>$subscriber->email,'name'=>$subscriber->name,'status'=>$subscriber->status,'locale'=>$subscriber->locale,
                'consented_at'=>$subscriber->consented_at?->toIso8601String(),'unsubscribed_at'=>$subscriber->unsubscribed_at?->toIso8601String(),
            ])->values(),
            'campaigns'=>$campaigns->map(fn ($campaign)=>[
                'id'=>$campaign->id,'name'=>$campaign->name,'subject'=>$campaign->subject,'status'=>$campaign->status,'list'=>$campaign->list?->name,
                'document'=>$campaign->document?->title,'scheduled_at'=>$campaign->scheduled_at?->toIso8601String(),'sent_at'=>$campaign->sent_at?->toIso8601String(),
                'delivered_count'=>(int) $campaign->delivered_count,'failed_count'=>(int) $campaign->failed_count,
            ])->values(),
            'documents'=>Document::query()->whereIn('type',['article','blog_post','document'])->where('status','published')->latest('updated_at')->limit(200)->get(['id','title','type'])->values(),
            'languages'=>collect((array) config('localization.supported', []))->map(fn (array $language, string $code): array => [
                'value'=>$code,
                'label'=>(string) ($language['name'] ?? strtoupper($code)),
                'native'=>(string) ($language['native'] ?? ''),
                'country'=>(string) ($language['country'] ?? ''),
                'flag_asset'=>(string) ($language['flag_asset'] ?? ''),
            ])->values(),
            'summary'=>[
                'active_subscribers'=>NewsletterSubscriber::query()->where('status','active')->count(),
                'lists'=>NewsletterList::query()->where('status','active')->count(),
                'campaigns'=>NewsletterCampaign::query()->count(),
                'scheduled'=>NewsletterCampaign::query()->where('status','scheduled')->count(),
            ],
        ]);
    }

    public function createList(Request $request, AuditManager $audit): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:180'],'description'=>['nullable','string','max:2000']]);
        $base=Str::slug($data['name']) ?: 'newsletter-list'; $slug=$base; $i=2; while (NewsletterList::query()->where('slug',$slug)->exists()) $slug=$base.'-'.$i++;
        $list=NewsletterList::query()->create(['uuid'=>(string) Str::uuid(),'name'=>$data['name'],'slug'=>$slug,'description'=>$data['description'] ?? null,'status'=>'active','metadata'=>[]]);
        $audit->record('newsletter.list.created',$list,[]); return back()->with('success','Newsletter list created.');
    }

    public function subscriber(Request $request, NewsletterSubscriptionManager $subscriptions, AuditManager $audit): RedirectResponse
    {
        $data=$request->validate(['email'=>['required','email:rfc','max:320'],'name'=>['nullable','string','max:180'],'locale'=>['required','string','max:12'],'list_id'=>['nullable','integer',new TenantExists('nx_newsletter_lists')]]);
        $list=!empty($data['list_id'])?NewsletterList::query()->find((int)$data['list_id']):null;
        $subscriber=$subscriptions->subscribe($data['email'],$data['name'] ?? null,$data['locale'],'admin',$list);
        $audit->record('newsletter.subscriber.added',$subscriber,['list_id'=>$list?->id]); return back()->with('success','Subscriber added with consent source recorded as Admin.');
    }

    public function subscriberStatus(Request $request, NewsletterSubscriber $subscriber, NewsletterSubscriptionManager $subscriptions): RedirectResponse
    {
        $data=$request->validate(['status'=>['required',Rule::in(['active','unsubscribed'])]]);
        if ($data['status']==='unsubscribed') $subscriptions->unsubscribe($subscriber);
        else $subscriber->forceFill(['status'=>'active','unsubscribed_at'=>null])->save();
        return back()->with('success','Subscriber status updated.');
    }

    public function campaign(Request $request, AuditManager $audit): RedirectResponse
    {
        $data=$request->validate([
            'name'=>['required','string','max:180'],'subject'=>['required','string','max:255'],'preview_text'=>['nullable','string','max:500'],
            'document_id'=>['nullable','integer',new TenantExists('nx_documents')],'list_id'=>['required','integer',new TenantExists('nx_newsletter_lists')],
            'scheduled_at'=>['nullable','date'],
        ]);
        $campaign=NewsletterCampaign::query()->create([
            'uuid'=>(string) Str::uuid(),'name'=>$data['name'],'subject'=>$data['subject'],'preview_text'=>$data['preview_text'] ?? null,
            'document_id'=>$data['document_id'] ?? null,'list_id'=>(int)$data['list_id'],'status'=>!empty($data['scheduled_at'])?'scheduled':'draft',
            'scheduled_at'=>$data['scheduled_at'] ?? null,'created_by'=>$request->user()?->id,'metadata'=>[],
        ]);
        $audit->record('newsletter.campaign.created',$campaign,['status'=>$campaign->status]); return back()->with('success','Newsletter campaign created.');
    }

    public function queue(NewsletterCampaign $campaign, NewsletterDispatchService $dispatch, AuditManager $audit): RedirectResponse
    {
        try { $count=$dispatch->queue($campaign); } catch (\RuntimeException $exception) { return back()->withErrors(['campaign'=>$exception->getMessage()]); }
        $audit->record('newsletter.campaign.queued',$campaign,['recipients'=>$count]);
        return back()->with('success',"Campaign queued for {$count} subscriber(s). Delivery continues through the configured queue worker.");
    }
}
