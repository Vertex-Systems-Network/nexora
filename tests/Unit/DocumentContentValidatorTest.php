<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Documents\Blocks\BlockDefinition;
use App\Nexora\Documents\Blocks\BlockRegistry;
use App\Nexora\Documents\Services\DocumentContentValidator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DocumentContentValidatorTest extends TestCase
{
    #[Test]
    public function it_normalizes_registered_blocks_into_the_canonical_document_tree(): void
    {
        $blocks = new BlockRegistry();
        $blocks->register(new BlockDefinition('paragraph', 'Paragraph', 'text'));
        $validator = new DocumentContentValidator($blocks);

        $content = $validator->normalize([
            'version' => 1,
            'blocks' => [
                ['type' => 'paragraph', 'data' => ['text' => 'Hello Nexora']],
            ],
        ]);

        self::assertSame(1, $content['version']);
        self::assertSame('paragraph', $content['blocks'][0]['type']);
        self::assertSame(['text' => 'Hello Nexora'], $content['blocks'][0]['data']);
        self::assertNotSame('', $content['blocks'][0]['id']);
    }

    #[Test]
    public function it_rejects_unregistered_block_types(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DocumentContentValidator(new BlockRegistry()))->normalize([
            'version' => 1,
            'blocks' => [['type' => 'unknown', 'data' => []]],
        ]);
    }
}
