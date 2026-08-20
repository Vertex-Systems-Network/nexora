<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\SearchQueryLog;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;
use Illuminate\Support\Facades\DB;

final class AutomationSearchObserver
{
    public function created(SearchQueryLog $log): void
    {
        if ($log->scope !== 'public' || $log->results_count !== 0) return;
        $payload = ['search'=>['query'=>$log->normalized_query,'locale'=>$log->locale,'results_count'=>0]];
        DB::afterCommit(fn () => app(AutomationEventBusContract::class)->emit('search.zero_results',$payload,'search_query',$log->id));
    }
}
