<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Documents\Editorial\EditorialWorkflowRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EditorialWorkflowRegistryTest extends TestCase
{
    #[Test]
    public function it_exposes_human_readable_states_and_valid_transitions(): void
    {
        $workflow = new EditorialWorkflowRegistry();
        self::assertContains('review', $workflow->keys());
        self::assertContains('approved', array_column($workflow->availableFrom('review'), 'key'));
        $workflow->assertTransition('draft', 'review');
    }

    #[Test]
    public function it_rejects_invalid_editorial_jumps(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new EditorialWorkflowRegistry())->assertTransition('idea', 'published');
    }
}
