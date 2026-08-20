<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Installation\Exceptions\InstallationCancelledException;
use App\Nexora\Installation\InstallationRunControl;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InstallationRunControlTest extends TestCase
{
    #[Test]
    public function cancellation_is_honored_at_safe_checkpoints_and_rejected_in_protected_stages(): void
    {
        $control = app(InstallationRunControl::class);
        $runId = bin2hex(random_bytes(12));
        $session = 'test-session-'.bin2hex(random_bytes(5));

        try {
            $control->start($runId, $session);
            $result = $control->requestCancel($runId, $session);
            self::assertTrue($result['ok']);

            $this->expectException(InstallationCancelledException::class);
            $control->throwIfCancelled($runId);
        } finally {
            @unlink(base_path('storage/app/nexora/installation-control/'.$runId.'.json'));
        }
    }

    #[Test]
    public function protected_stage_cannot_be_cancelled(): void
    {
        $control = app(InstallationRunControl::class);
        $runId = bin2hex(random_bytes(12));
        $session = 'test-session-'.bin2hex(random_bytes(5));

        try {
            $control->start($runId, $session);
            $control->update($runId, 'migrations', false);
            $result = $control->requestCancel($runId, $session);

            self::assertFalse($result['ok']);
            self::assertFalse($result['cancellable']);
        } finally {
            @unlink(base_path('storage/app/nexora/installation-control/'.$runId.'.json'));
        }
    }
}
