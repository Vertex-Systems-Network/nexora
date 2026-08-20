<?php

declare(strict_types=1);

namespace Tests\Unit\Cloud;

use App\Nexora\Cloud\Contracts\ObjectStorageContract;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ObjectStorageContractTest extends TestCase
{
    public function test_storage_contract_uses_configured_laravel_disk(): void
    {
        Storage::fake('local');
        config()->set('nexora_cloud.object_storage_disk', 'local');
        $storage = app(ObjectStorageContract::class);
        $storage->put('cloud-test/object.txt', 'nexora');
        self::assertTrue($storage->exists('cloud-test/object.txt'));
        self::assertSame('nexora', $storage->get('cloud-test/object.txt'));
        $storage->delete('cloud-test/object.txt');
        self::assertFalse($storage->exists('cloud-test/object.txt'));
    }
}
