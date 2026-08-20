<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Foundation\Filesystem\AtomicFileWriter;
use App\Nexora\Foundation\Transfers\TransferSafety;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class TransferSafetyTest extends TestCase
{
    private function service(string $root): TransferSafety
    {
        config()->set('nexora-transfers.temporary_root',$root.'/tmp');
        config()->set('nexora-transfers.minimum_free_bytes',0);
        return new TransferSafety(new AtomicFileWriter());
    }

    #[Test]
    public function bounded_atomic_copy_preserves_bytes_and_checksum(): void
    {
        $root=sys_get_temp_dir().DIRECTORY_SEPARATOR.'nx-transfer-'.bin2hex(random_bytes(5));
        @mkdir($root,0700,true);
        $source=$root.'/source.bin';$target=$root.'/target.bin';
        $payload=random_bytes(131072);
        file_put_contents($source,$payload);
        try{
            $result=$this->service($root)->copyFileAtomically($source,$target,262144);
            self::assertSame(strlen($payload),$result['bytes']);
            self::assertSame(hash('sha256',$payload),$result['sha256']);
            self::assertSame($payload,file_get_contents($target));
        }finally{@unlink($source);@unlink($target);@rmdir($root.'/tmp');@rmdir($root);}
    }

    #[Test]
    public function over_budget_stream_never_publishes_partial_destination(): void
    {
        $root=sys_get_temp_dir().DIRECTORY_SEPARATOR.'nx-transfer-'.bin2hex(random_bytes(5));
        @mkdir($root,0700,true);
        $source=fopen('php://temp','w+b');
        fwrite($source,str_repeat('x',8192));rewind($source);
        $target=$root.'/target.bin';
        try{
            $this->expectException(RuntimeException::class);
            $this->service($root)->copyStreamAtomically($source,$target,4096,8192);
        }finally{
            fclose($source);
            self::assertFileDoesNotExist($target);
            foreach(glob($root.'/.nexora-*.part')?:[] as $part)@unlink($part);
            @rmdir($root.'/tmp');@rmdir($root);
        }
    }
}
