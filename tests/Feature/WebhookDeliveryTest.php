<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\WebhookDelivery;
use App\Models\WebhookDestination;
use App\Nexora\Automation\Services\WebhookDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

final class WebhookDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_outbound_webhook_is_signed_and_redirects_are_not_followed(): void
    {
        Http::fake(['https://example.com/hooks/nexora'=>Http::response('',204)]);
        $destination=WebhookDestination::query()->create(['uuid'=>(string)Str::uuid(),'name'=>'Example','url'=>'https://example.com/hooks/nexora','secret'=>'outbound-secret','enabled'=>true,'timeout_seconds'=>5,'max_attempts'=>3,'headers'=>[]]);
        $delivery=WebhookDelivery::query()->create(['uuid'=>(string)Str::uuid(),'webhook_destination_id'=>$destination->id,'event_key'=>'document.published','idempotency_key'=>'delivery-test-1','payload'=>['hello'=>'world'],'status'=>'queued']);
        app(WebhookDeliveryService::class)->deliver($delivery->load('destination'));
        self::assertSame('delivered',$delivery->fresh()->status);
        Http::assertSent(function (ClientRequest $request): bool {
            return $request->url()==='https://example.com/hooks/nexora'
                && str_starts_with((string)$request->header('X-Nexora-Signature')[0],'v1=')
                && $request->header('Idempotency-Key')[0]==='delivery-test-1';
        });
    }
}
