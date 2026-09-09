<?php

declare(strict_types=1);

namespace Tests\Unit\Certification;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

final class SourceAttestationTest extends TestCase
{
    #[Test]
    public function generated_theme_projection_is_excluded_while_authoritative_theme_sources_are_attested(): void
    {
        require_once base_path('scripts/lib/source-attestation.php');

        $root=storage_path('framework/testing-nexora-source-attestation-'.bin2hex(random_bytes(4)));

        try {
            $themeDirectory=$root.'/themes/nexora.base/1.0.0';
            $projectionDirectory=$root.'/public/nexora-themes/nexora.base/1.0.0';
            self::assertTrue(mkdir($themeDirectory,0775,true));
            self::assertTrue(mkdir($projectionDirectory,0775,true));

            file_put_contents($themeDirectory.'/theme.css','authoritative-v1');
            file_put_contents($projectionDirectory.'/theme.css','projection-v1');

            $before=\nexoraComputeSourceAttestation($root);
            $paths=array_column($before['files'],'path');

            self::assertContains('themes/nexora.base/1.0.0/theme.css',$paths);
            self::assertNotContains('public/nexora-themes/nexora.base/1.0.0/theme.css',$paths);

            file_put_contents($projectionDirectory.'/theme.css','projection-v2');
            $afterProjectionMutation=\nexoraComputeSourceAttestation($root);

            self::assertSame($before['tree_sha256'],$afterProjectionMutation['tree_sha256']);
            self::assertSame($before['file_count'],$afterProjectionMutation['file_count']);

            file_put_contents($themeDirectory.'/theme.css','authoritative-v2');
            $afterAuthoritativeMutation=\nexoraComputeSourceAttestation($root);

            self::assertNotSame($before['tree_sha256'],$afterAuthoritativeMutation['tree_sha256']);
        } finally {
            $this->removeDirectory($root);
        }
    }

    private function removeDirectory(string $path): void
    {
        if(!is_dir($path)) return;

        $iterator=new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path,FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach($iterator as $item){
            if($item->isDir()) rmdir($item->getPathname());
            else unlink($item->getPathname());
        }

        rmdir($path);
    }
}
