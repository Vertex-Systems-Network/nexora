<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

/** AI-GOV-AUTOMATION-100 / SYS-AI-GOVERNANCE-AUTOMATION: PR-to-unit traceability rules. */
final class PrUnitTraceabilityTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
        require_once $this->root.'/scripts/pr-unit-traceability.php';
    }

    public function test_a_registered_stage_or_unit_in_the_title_passes(): void
    {
        $result = \nexora_pr_traceability($this->root, 'SECURITY-BASELINE-200: passkeys', '', 'someone');

        $this->assertTrue($result['ok']);
        $this->assertSame(['SECURITY-BASELINE-200'], $result['referenced']);
    }

    public function test_a_unit_id_in_the_body_passes(): void
    {
        $result = \nexora_pr_traceability($this->root, 'Fix login', "Implements SYS-MFA-PASSKEY.\nRefs #12", 'someone');

        $this->assertTrue($result['ok']);
        $this->assertSame(['SYS-MFA-PASSKEY'], $result['referenced']);
    }

    public function test_untraced_or_unknown_ids_fail(): void
    {
        $this->assertFalse(\nexora_pr_traceability($this->root, 'Add a cool feature', 'No plan.', 'someone')['ok']);
        $this->assertFalse(\nexora_pr_traceability($this->root, 'NOT-A-REAL-UNIT-999: thing', '', 'someone')['ok']);
        $this->assertFalse(\nexora_pr_traceability($this->root, '', '', 'someone')['ok']);
    }

    public function test_partial_tokens_do_not_count(): void
    {
        // A registered id embedded in a longer token is not a reference to it.
        $this->assertFalse(\nexora_pr_traceability($this->root, 'SEC-BASELINE-EXTRA work', '', 'someone')['ok']);
        $this->assertFalse(\nexora_pr_traceability($this->root, 'XSEC-BASELINE work', '', 'someone')['ok']);
    }

    public function test_dependency_bot_prs_are_exempt_but_people_are_not(): void
    {
        $this->assertTrue(\nexora_pr_traceability($this->root, 'chore(deps): bump vitest', '', 'dependabot[bot]')['exempt']);
        $this->assertFalse(\nexora_pr_traceability($this->root, 'chore(deps): bump vitest', '', 'dependabot')['ok']);
    }

    public function test_every_registered_unit_is_known(): void
    {
        $known = \nexora_traceability_known_ids($this->root);

        foreach (glob($this->root.'/.ai/registry/*.json') ?: [] as $registry) {
            foreach (json_decode((string) file_get_contents($registry), true)['units'] as $unit) {
                $this->assertArrayHasKey($unit['id'], $known);
            }
        }
        $this->assertArrayHasKey('AI-GOV-AUTOMATION-100', $known);
    }
}
