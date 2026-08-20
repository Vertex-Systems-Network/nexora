<?php

declare(strict_types=1);

namespace App\Nexora\Extensions\Services;

use App\Models\MarketplaceCatalogItem;
use App\Models\MarketplaceSource;
use App\Nexora\Automation\Services\WebhookUrlPolicy;
use App\Nexora\Foundation\Network\ApprovedHttpClient;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class MarketplaceCatalogService
{
    public function __construct(private WebhookUrlPolicy $urls,private ApprovedHttpClient $http) {}
    public function sync(MarketplaceSource $source): int
    {
        $url=rtrim($source->base_url,'/').'/nexora-marketplace.json';
        $this->urls->assertAllowed($url,true);
        $response=$this->http->external($url)->acceptJson()->timeout(12)->get($url);
        if (! $response->successful()) throw new RuntimeException('Marketplace catalog returned HTTP '.$response->status().'.');
        $payload=$response->json(); if (! is_array($payload) || ! is_array($payload['packages'] ?? null)) throw new RuntimeException('Marketplace catalog format is invalid.');
        $count=0;
        foreach ($payload['packages'] as $item) {
            if (! is_array($item)) continue;
            foreach (['id','name','type','version','artifact_url'] as $field) if (! is_string($item[$field] ?? null) || trim((string)$item[$field])==='') continue 2;
            if (! in_array($item['type'],['extension','app','integration','studio-pack'],true)) continue;
            $this->urls->assertAllowed((string)$item['artifact_url'],false);
            $catalogItem = MarketplaceCatalogItem::query()->firstOrNew([
                'source_id'=>$source->id,
                'package_identifier'=>$item['id'],
            ]);
            if (! $catalogItem->exists) {
                $catalogItem->id = (string) Str::uuid();
            }
            $catalogItem->fill([
                'name'=>$item['name'],'type'=>$item['type'],'latest_version'=>$item['version'],'description'=>(string)($item['description']??''),
                'publisher_key_id'=>$item['publisher_key_id']??null,'artifact_url'=>$item['artifact_url'],'artifact_sha256'=>$item['sha256']??null,'metadata'=>$item['metadata']??[],'synced_at'=>now(),
            ]);
            $catalogItem->save();
            $count++;
        }
        $source->forceFill(['last_synced_at'=>now(),'last_error'=>null])->save(); return $count;
    }
}
