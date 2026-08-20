<?php

declare(strict_types=1);

namespace Tests\Unit\Nexora\SupplyChain;

use App\Nexora\Security\SupplyChain\Services\PackageContentDigest;
use Tests\TestCase;
use ZipArchive;

final class PackageContentDigestTest extends TestCase
{
    public function test_signature_manifest_is_excluded_but_package_content_is_tamper_evident(): void
    {
        if (! class_exists(ZipArchive::class)) self::markTestSkipped('ZipArchive is required.');
        $one = tempnam(sys_get_temp_dir(), 'nx-digest-').'.zip';
        $two = tempnam(sys_get_temp_dir(), 'nx-digest-').'.zip';
        $three = tempnam(sys_get_temp_dir(), 'nx-digest-').'.zip';
        try {
            $this->zip($one, ['a.txt'=>'same','nexora.signature.json'=>'{"signature":"one"}']);
            $this->zip($two, ['a.txt'=>'same','nexora.signature.json'=>'{"signature":"two"}']);
            $this->zip($three, ['a.txt'=>'changed','nexora.signature.json'=>'{"signature":"two"}']);
            $digest = new PackageContentDigest();
            self::assertSame($digest->calculate($one), $digest->calculate($two));
            self::assertNotSame($digest->calculate($one), $digest->calculate($three));
        } finally {
            @unlink($one); @unlink($two); @unlink($three);
        }
    }

    /** @param array<string,string> $files */
    private function zip(string $path, array $files): void
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);
        foreach ($files as $name=>$contents) self::assertTrue($zip->addFromString($name, $contents));
        $zip->close();
    }
}
