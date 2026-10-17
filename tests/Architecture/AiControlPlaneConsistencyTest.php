<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/scripts/ai-control-plane-verify.php';

final class AiControlPlaneConsistencyTest extends TestCase
{
    private string $fixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixture = sys_get_temp_dir().'/nexora-ai-cp-'.bin2hex(random_bytes(6));
        $this->write('.ai/roadmap/stages.md', "| # | Stage |\n|---|---|\n| 1 | `RUNTIME-CLOSURE-001` | x |\n| 2 | `CORE-QA-001` | y |\n");
        $this->writeJson('.ai/registry/development-units.json', ['units' => [
            ['id' => 'SYS-CORE-ADMIN-QA', 'status' => 'PLANNED', 'parent_stage_id' => 'CORE-QA-001', 'depends_on' => ['RUNTIME-CLOSURE-001']],
        ]]);
        $this->writeJson('.ai/state.json', [
            'current_stage' => ['id' => 'CORE-QA-001'],
            'active_development_units' => ['SYS-CORE-ADMIN-QA'],
            'completed_control_stages' => [['id' => 'RUNTIME-CLOSURE-001', 'waiver' => 'WVR-TEST-001']],
        ]);
        $this->writeJson('.ai/waivers/WVR-TEST-001.json', ['id' => 'WVR-TEST-001']);
        $this->write('.ai/resume/CURRENT-STATE.yaml', "current_stage:\n  id: CORE-QA-001\n  unit: SYS-CORE-ADMIN-QA\n");
        $this->write('.ai/plans/active.md', "# Current plan\n\nCORE-QA-001 via SYS-CORE-ADMIN-QA.\n\n# Older plan\n\nRUNTIME-CLOSURE-001.\n");
        $this->write('.ai/quality/definition-of-done.md', "# Definition of Done\n");
    }

    public function test_stale_active_plan_and_missing_dod_are_rejected(): void
    {
        $this->write('.ai/plans/active.md', "# Latest plan\n\nRUNTIME-CLOSURE-001 only.\n\n# Older\n\nCORE-QA-001 SYS-CORE-ADMIN-QA\n");
        unlink($this->fixture.'/.ai/quality/definition-of-done.md');

        $errors = implode("\n", nexora_ai_control_plane_errors($this->fixture));
        self::assertStringContainsString('Latest plans/active.md section does not reference CORE-QA-001', $errors);
        self::assertStringContainsString('Latest plans/active.md section does not reference SYS-CORE-ADMIN-QA', $errors);
        self::assertStringContainsString('definition-of-done.md is missing or empty', $errors);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->fixture);
        parent::tearDown();
    }

    public function test_repository_control_plane_is_consistent(): void
    {
        self::assertSame([], nexora_ai_control_plane_errors(dirname(__DIR__, 2)));
    }

    public function test_consistent_fixture_passes(): void
    {
        self::assertSame([], nexora_ai_control_plane_errors($this->fixture));
    }

    public function test_unregistered_active_unit_and_unknown_stage_are_rejected(): void
    {
        $this->writeJson('.ai/state.json', [
            'current_stage' => ['id' => 'NOT-A-STAGE-001'],
            'active_development_units' => ['SYS-UNPLANNED'],
        ]);

        $errors = implode("\n", nexora_ai_control_plane_errors($this->fixture));
        self::assertStringContainsString('current_stage.id is not a canonical stage', $errors);
        self::assertStringContainsString('active unit SYS-UNPLANNED is not registered', $errors);
    }

    public function test_registry_dangling_references_and_duplicates_are_rejected(): void
    {
        $this->writeJson('.ai/registry/extra-units.json', ['units' => [
            ['id' => 'SYS-CORE-ADMIN-QA', 'status' => 'PLANNED', 'parent_stage_id' => 'CORE-QA-001'],
            ['id' => 'SYS-ORPHAN', 'status' => 'PLANNED', 'parent_stage_id' => 'GHOST-100', 'depends_on' => ['MISSING-200']],
            ['id' => 'SYS-NO-STATUS', 'parent_stage_id' => 'CORE-QA-001'],
        ]]);

        $errors = implode("\n", nexora_ai_control_plane_errors($this->fixture));
        self::assertStringContainsString('Duplicate development unit id SYS-CORE-ADMIN-QA', $errors);
        self::assertStringContainsString('SYS-ORPHAN references unknown stage GHOST-100', $errors);
        self::assertStringContainsString('SYS-ORPHAN depends on unknown stage/unit MISSING-200', $errors);
        self::assertStringContainsString('SYS-NO-STATUS is missing status', $errors);
    }

    public function test_resume_drift_and_missing_waiver_are_rejected(): void
    {
        $this->write('.ai/resume/CURRENT-STATE.yaml', "current_stage:\n  id: RUNTIME-CLOSURE-001\n  unit: SYS-RUNTIME-IDENTITY\n");
        unlink($this->fixture.'/.ai/waivers/WVR-TEST-001.json');

        $errors = implode("\n", nexora_ai_control_plane_errors($this->fixture));
        self::assertStringContainsString('CURRENT-STATE.yaml stage RUNTIME-CLOSURE-001 disagrees', $errors);
        self::assertStringContainsString('unit SYS-RUNTIME-IDENTITY is not an active unit', $errors);
        self::assertStringContainsString('cites missing waiver WVR-TEST-001', $errors);
    }

    private function writeJson(string $path, array $data): void
    {
        $this->write($path, (string) json_encode($data, JSON_PRETTY_PRINT));
    }

    private function write(string $path, string $contents): void
    {
        $full = $this->fixture.'/'.$path;
        if (! is_dir(dirname($full))) {
            mkdir(dirname($full), 0777, true);
        }
        file_put_contents($full, $contents);
    }
}
