<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;
use App\Nexora\Distribution\Services\NewsletterSubscriptionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NewsletterSubscriptionController extends Controller
{
    public function confirm(string $token): View
    {
        $subscriber=NewsletterSubscriber::query()->where('unsubscribe_token',$token)->firstOrFail();
        return view('newsletter.unsubscribe',['subscriber'=>$subscriber]);
    }

    public function unsubscribe(Request $request, string $token, NewsletterSubscriptionManager $subscriptions): RedirectResponse
    {
        $subscriber=NewsletterSubscriber::query()->where('unsubscribe_token',$token)->firstOrFail();
        $subscriptions->unsubscribe($subscriber);
        return back()->with('unsubscribed',true);
    }

    public function subscribe(Request $request, NewsletterSubscriptionManager $subscriptions): RedirectResponse
    {
        $data=$request->validate(['email'=>['required','email:rfc','max:320'],'name'=>['nullable','string','max:180'],'consent'=>['accepted']]);
        $list=NewsletterList::query()->where('status','active')->orderBy('id')->first();
        $subscriptions->subscribe($data['email'],$data['name'] ?? null,app()->getLocale(),'public-form',$list);
        return back()->with('subscribed',true);
    }
}
