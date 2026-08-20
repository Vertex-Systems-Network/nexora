<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\NewsletterSubscriber;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;
use Illuminate\Support\Facades\DB;

final class AutomationSubscriberObserver
{
    public function created(NewsletterSubscriber $subscriber): void
    {
        $payload = ['subscriber'=>['id'=>$subscriber->id,'email'=>$subscriber->email,'locale'=>$subscriber->locale,'status'=>$subscriber->status]];
        DB::afterCommit(fn () => app(AutomationEventBusContract::class)->emit('newsletter.subscribed',$payload,'newsletter_subscriber',$subscriber->id));
    }
}
