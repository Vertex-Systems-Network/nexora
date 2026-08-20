<?php

declare(strict_types=1);

namespace App\Nexora\Distribution\Services;

use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

final class NewsletterSubscriptionManager
{
    public function subscribe(string $email, ?string $name, string $locale, string $source, ?NewsletterList $list = null): NewsletterSubscriber
    {
        $email = mb_strtolower(trim($email));
        $subscriber = NewsletterSubscriber::query()->firstOrNew(['email'=>$email]);
        $subscriber->fill([
            'uuid'=>$subscriber->uuid ?: (string) Str::uuid(),'name'=>trim((string) $name) ?: $subscriber->name,'status'=>'active',
            'locale'=>$locale,'consent_source'=>$source,'consented_at'=>now(),'unsubscribed_at'=>null,
            'unsubscribe_token'=>$subscriber->unsubscribe_token ?: bin2hex(random_bytes(32)),'metadata'=>$subscriber->metadata ?? [],
        ])->save();
        if ($list) $list->subscribers()->syncWithoutDetaching([$subscriber->id=>['status'=>'subscribed','subscribed_at'=>now(),'unsubscribed_at'=>null]]);
        return $subscriber;
    }

    public function unsubscribe(NewsletterSubscriber $subscriber): void
    {
        $subscriber->forceFill(['status'=>'unsubscribed','unsubscribed_at'=>now()])->save();
        DB::table('nx_newsletter_list_subscribers')->where('subscriber_id', $subscriber->id)->update(['status'=>'unsubscribed','unsubscribed_at'=>now(),'updated_at'=>now()]);
    }
}
