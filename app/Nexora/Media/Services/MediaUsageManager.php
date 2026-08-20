<?php

declare(strict_types=1);

namespace App\Nexora\Media\Services;

use App\Models\MediaAsset;
use App\Models\MediaUsage;

final class MediaUsageManager
{
    /** @param array<string,mixed> $metadata */
    public function assign(?MediaAsset $asset, string $resourceType, int $resourceId, string $slot, array $metadata = []): void
    {
        MediaUsage::query()->where('resource_type', $resourceType)->where('resource_id', $resourceId)->where('slot', $slot)->delete();
        if (! $asset) return;
        MediaUsage::query()->updateOrCreate(
            ['asset_id'=>$asset->id,'resource_type'=>$resourceType,'resource_id'=>$resourceId,'slot'=>$slot],
            ['metadata'=>$metadata]
        );
    }
}
