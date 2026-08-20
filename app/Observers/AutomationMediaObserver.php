<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\MediaAsset;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;
use Illuminate\Support\Facades\DB;

final class AutomationMediaObserver
{
    public function created(MediaAsset $asset): void
    {
        $payload = ['media'=>['id'=>$asset->id,'uuid'=>$asset->uuid,'media_type'=>$asset->media_type,'mime_type'=>$asset->mime_type,'original_name'=>$asset->original_name,'visibility'=>$asset->visibility]];
        DB::afterCommit(fn () => app(AutomationEventBusContract::class)->emit('media.uploaded',$payload,'media',$asset->id));
    }
}
