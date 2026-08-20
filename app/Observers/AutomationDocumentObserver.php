<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Document;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;
use Illuminate\Support\Facades\DB;

final class AutomationDocumentObserver
{
    public function created(Document $document): void
    {
        $this->afterCommit('document.created', $document);
    }

    public function updated(Document $document): void
    {
        $payload = $this->payload($document);
        $publishedTransition = $document->wasChanged('status') && $document->status === 'published';
        $idempotency = 'document-published:'.$document->id.':'.($document->updated_at?->timestamp ?? time());
        DB::afterCommit(function () use ($document, $payload, $publishedTransition, $idempotency): void {
            app(AutomationEventBusContract::class)->emit('document.updated', $payload, 'document', $document->id);
            if ($publishedTransition) {
                app(AutomationEventBusContract::class)->emit('document.published', $payload, 'document', $document->id, $idempotency);
            }
        });
    }

    private function afterCommit(string $event, Document $document): void
    {
        $payload = $this->payload($document);
        DB::afterCommit(fn () => app(AutomationEventBusContract::class)->emit($event, $payload, 'document', $document->id));
    }

    private function payload(Document $document): array
    {
        return ['document'=>['id'=>$document->id,'type'=>$document->type,'status'=>$document->status,'title'=>$document->title,'slug'=>$document->slug,'locale'=>$document->locale ?? 'en','published_at'=>$document->published_at?->toIso8601String()]];
    }
}
