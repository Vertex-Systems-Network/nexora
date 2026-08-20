<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\DocumentRevision;
use App\Nexora\Documents\Services\DocumentRevisionComparator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DocumentRevisionComparatorTest extends TestCase
{
    #[Test]
    public function it_compares_semantic_blocks_by_stable_block_id(): void
    {
        $before = new DocumentRevision(['revision' => 1, 'title' => 'A', 'content' => ['version' => 1, 'blocks' => [['id' => 'one', 'type' => 'paragraph', 'data' => ['text' => 'Old']]]]]);
        $after = new DocumentRevision(['revision' => 2, 'title' => 'B', 'content' => ['version' => 1, 'blocks' => [['id' => 'one', 'type' => 'paragraph', 'data' => ['text' => 'New']], ['id' => 'two', 'type' => 'heading', 'data' => ['text' => 'Added']]]]]);
        $diff = (new DocumentRevisionComparator())->compare($before, $after);
        self::assertSame(1, $diff['summary']['changed']);
        self::assertSame(1, $diff['summary']['added']);
        self::assertTrue($diff['fields'][0]['changed']);
    }
}
