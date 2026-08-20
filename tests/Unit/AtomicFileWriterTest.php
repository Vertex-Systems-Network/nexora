<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Foundation\Filesystem\AtomicFileWriter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AtomicFileWriterTest extends TestCase
{
    #[Test]
    public function it_atomically_replaces_state_without_leaving_temp_files(): void
    {
        $directory=storage_path('framework/testing-nexora-atomic');
        if(is_dir($directory)) foreach(glob($directory.'/*')?:[] as $path) @unlink($path);
        @mkdir($directory,0775,true);
        $path=$directory.'/state.json';
        $writer=app(AtomicFileWriter::class);
        $writer->write($path,"one\n",0775,0600);
        $writer->write($path,"two\n",0775,0600);
        self::assertSame("two\n",file_get_contents($path));
        self::assertSame([],glob($directory.'/.nexora-atomic-*')?:[]);
        @unlink($path);@rmdir($directory);
    }

    #[Test]
    public function verified_move_publishes_destination_and_removes_source(): void
    {
        $directory=storage_path('framework/testing-nexora-move');@mkdir($directory,0775,true);
        $source=$directory.'/source.bin';$destination=$directory.'/destination.bin';
        file_put_contents($source,str_repeat('nexora',128));
        app(AtomicFileWriter::class)->moveVerified($source,$destination);
        self::assertFileDoesNotExist($source);
        self::assertSame(str_repeat('nexora',128),file_get_contents($destination));
        @unlink($destination);@rmdir($directory);
    }
}
