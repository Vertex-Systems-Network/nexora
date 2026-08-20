<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Documents\Blocks\BlockRegistry;
use App\Nexora\Documents\Types\DocumentTypeRegistry;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DocumentEngineRegistryTest extends TestCase
{
    #[Test]
    public function it_registers_the_neutral_document_type_and_core_blocks(): void
    {
        $types = app(DocumentTypeRegistry::class);
        $blocks = app(BlockRegistry::class);

        self::assertArrayHasKey('document', $types->all());
        foreach (['paragraph', 'heading', 'list', 'quote', 'code', 'divider'] as $block) {
            self::assertTrue($blocks->has($block), "Missing core document block [{$block}].");
        }
    }
}
