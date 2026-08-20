<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use App\Nexora\Seo\Schema\SchemaGraph;
use App\Nexora\Seo\Schema\SchemaNode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SchemaGraphTest extends TestCase
{
    public function test_same_node_id_cannot_be_silently_retyped(): void
    {
        $graph = new SchemaGraph();
        $graph->add(new SchemaNode('#page', 'WebPage', ['name' => 'Page']));

        $this->expectException(InvalidArgumentException::class);
        $graph->add(new SchemaNode('#page', 'Organization', ['name' => 'Injected organization']));
    }

    public function test_higher_priority_same_type_can_refine_node_without_duplicate_output(): void
    {
        $graph = new SchemaGraph();
        $graph->add(new SchemaNode('#page', 'WebPage', ['name' => 'Original'], 'core', 100));
        $graph->add(new SchemaNode('#page', 'WebPage', ['name' => 'Refined'], 'extension', 200));

        $payload = $graph->toArray();
        self::assertCount(1, $payload['@graph']);
        self::assertSame('Refined', $payload['@graph'][0]['name']);
    }
}
