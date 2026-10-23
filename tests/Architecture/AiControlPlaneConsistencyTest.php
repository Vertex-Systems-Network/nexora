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

    public function test_research_brief_must_exist_under_research_dir_with_every_section(): void
    {
        $this->setUnitResearch(['brief' => '.ai/research/missing.md']);
        self::assertStringContainsString('research.brief must be an existing .md file under .ai/research/', implode("\n", nexora_ai_control_plane_errors($this->fixture)));

        $this->write('.ai/outside.md', $this->brief());
        $this->setUnitResearch(['brief' => '.ai/research/../outside.md']);
        self::assertStringContainsString('research.brief must be an existing .md file under .ai/research/', implode("\n", nexora_ai_control_plane_errors($this->fixture)));

        $this->write('.ai/research/partial.md', "# Brief\n\n## Problem\n\nx\n");
        $this->setUnitResearch(['brief' => '.ai/research/partial.md']);
        $errors = implode("\n", nexora_ai_control_plane_errors($this->fixture));
        self::assertStringContainsString('is missing section "## Baseline"', $errors);
        self::assertStringContainsString('is missing section "## CTQs"', $errors);

        $this->write('.ai/research/notes.txt', $this->brief());
        $this->setUnitResearch(['brief' => '.ai/research/notes.txt']);
        self::assertStringContainsString('must be an existing .md file', implode("\n", nexora_ai_control_plane_errors($this->fixture)));

        $this->write('.ai/research/fenced.md', "```\n".$this->brief()."```\n");
        $this->setUnitResearch(['brief' => '.ai/research/fenced.md']);
        self::assertStringContainsString('is missing section "## Problem"', implode("\n", nexora_ai_control_plane_errors($this->fixture)));

        $this->write('.ai/research/full.md', $this->brief());
        $this->setUnitResearch(['brief' => '.ai/research/full.md']);
        self::assertSame([], nexora_ai_control_plane_errors($this->fixture));
    }

    public function test_confidence_without_voc_evidence_and_empty_baseline_are_rejected(): void
    {
        $this->setUnitResearch(['confidence' => 'high', 'voc_evidence' => [' '], 'baseline' => '']);
        $errors = implode("\n", nexora_ai_control_plane_errors($this->fixture));
        self::assertStringContainsString('claims research confidence high without research.voc_evidence', $errors);
        self::assertStringContainsString('research.baseline is empty; write UNKNOWN', $errors);

        $this->setUnitResearch(['confidence' => 'low', 'voc_evidence' => 'just a string']);
        self::assertStringContainsString('claims research confidence low without research.voc_evidence', implode("\n", nexora_ai_control_plane_errors($this->fixture)));

        $this->setUnitResearch(['confidence' => 'certain']);
        self::assertStringContainsString('research.confidence must be unknown, low, medium or high', implode("\n", nexora_ai_control_plane_errors($this->fixture)));

        $this->setUnitResearch(['confidence' => 'unknown', 'baseline' => 'UNKNOWN']);
        self::assertSame([], nexora_ai_control_plane_errors($this->fixture));
        $this->setUnitResearch(['confidence' => 'medium', 'voc_evidence' => ['issue #12'], 'baseline' => 'p95 420ms']);
        self::assertSame([], nexora_ai_control_plane_errors($this->fixture));
    }

    public function test_high_or_critical_active_unit_needs_a_brief_or_exception(): void
    {
        $this->setUnit(['security' => ['risk' => 'critical']]);
        self::assertStringContainsString('is ACTIVE with critical risk but has no research.brief or research.exception', implode("\n", nexora_ai_control_plane_errors($this->fixture)));

        // A bare string is not a waiver.
        $this->setUnit(['security' => ['risk' => 'high'], 'research' => ['exception' => 'n/a']]);
        self::assertStringContainsString('research.exception must be {reason, approver, expires', implode("\n", nexora_ai_control_plane_errors($this->fixture)));
        $this->setUnit(['security' => ['risk' => 'high'], 'research' => ['exception' => ['reason' => 'x', 'approver' => 'owner', 'expires' => '2000-01-01']]]);
        self::assertStringContainsString('and not expired', implode("\n", nexora_ai_control_plane_errors($this->fixture)));

        $this->setUnit(['security' => ['risk' => 'high'], 'quality' => ['method' => 'DMAIC'], 'research' => ['exception' => ['reason' => 'Defect fix under DMAIC.', 'approver' => 'owner', 'expires' => '2999-01-01']]]);
        self::assertSame([], nexora_ai_control_plane_errors($this->fixture));

        $this->write('.ai/research/full.md', $this->brief());
        $this->setUnit(['security' => ['risk' => 'critical'], 'quality' => ['method' => 'DMADV', 'fmea_required' => false], 'research' => ['brief' => '.ai/research/full.md']]);
        self::assertSame([], nexora_ai_control_plane_errors($this->fixture));

        // PLANNED high/critical units are not gated until they start.
        $this->setUnit(['security' => ['risk' => 'critical']], 'PLANNED');
        self::assertSame([], nexora_ai_control_plane_errors($this->fixture));
    }

    public function test_threat_model_reference_must_resolve_and_be_present_when_required(): void
    {
        $this->setUnit(['security' => ['risk' => 'moderate', 'threat_model' => '.ai/security/threat-models/ghost.md']]);
        self::assertStringContainsString('security.threat_model must be an existing .md file under .ai/security/threat-models/', implode("\n", nexora_ai_control_plane_errors($this->fixture)));

        $this->setUnit(['security' => ['risk' => 'moderate', 'threat_model_required' => true]]);
        self::assertStringContainsString('requires a threat model but has no security.threat_model', implode("\n", nexora_ai_control_plane_errors($this->fixture)));

        $this->write('.ai/security/threat-models/tm.md', "# TM\n");
        $this->setUnit(['security' => ['risk' => 'moderate', 'threat_model_required' => true, 'threat_model' => '.ai/security/threat-models/tm.md']]);
        self::assertSame([], nexora_ai_control_plane_errors($this->fixture));

        // Not retro-gated: a finished unit without a threat model path is fine, but a broken path is not.
        $this->setUnit(['security' => ['risk' => 'moderate', 'threat_model_required' => true]], 'SOURCE_DONE');
        self::assertSame([], nexora_ai_control_plane_errors($this->fixture));
    }

    public function test_high_risk_active_unit_declares_method_and_critical_states_fmea(): void
    {
        $this->write('.ai/research/full.md', $this->brief());
        $brief = ['brief' => '.ai/research/full.md'];

        $this->setUnit(['security' => ['risk' => 'high'], 'research' => $brief]);
        self::assertStringContainsString('declares no quality.method', implode("\n", nexora_ai_control_plane_errors($this->fixture)));

        $this->setUnit(['security' => ['risk' => 'high'], 'research' => $brief, 'quality' => ['method' => 'Agile']]);
        self::assertStringContainsString('quality.method must be lightweight, DMADV or DMAIC', implode("\n", nexora_ai_control_plane_errors($this->fixture)));

        $this->setUnit(['security' => ['risk' => 'critical'], 'research' => $brief, 'quality' => ['method' => 'DMADV']]);
        self::assertStringContainsString('does not state quality.fmea_required', implode("\n", nexora_ai_control_plane_errors($this->fixture)));

        $this->setUnit(['security' => ['risk' => 'critical'], 'research' => $brief, 'quality' => ['method' => 'DMADV', 'fmea_required' => false]]);
        self::assertSame([], nexora_ai_control_plane_errors($this->fixture));
    }

    public function test_fmea_and_control_plan_references_must_resolve(): void
    {
        $this->setUnit(['quality' => ['fmea_required' => true, 'fmea_ref' => '.ai/quality/fmea/ghost.md']]);
        self::assertStringContainsString('quality.fmea_ref is not an existing .md file under .ai/quality/fmea/', implode("\n", nexora_ai_control_plane_errors($this->fixture)));

        $this->write('.ai/quality/fmea/partial.md', "# FMEA\n\n## Context\n");
        $this->setUnit(['quality' => ['fmea_required' => true, 'fmea_ref' => '.ai/quality/fmea/partial.md']]);
        self::assertStringContainsString('is missing section "## Failure-mode table"', implode("\n", nexora_ai_control_plane_errors($this->fixture)));

        $this->write('.ai/quality/fmea/full.md', "# FMEA\n\n## Context\n\n## Failure-mode table\n\n## Control evidence\n");
        $this->setUnit(['quality' => ['fmea_required' => true, 'fmea_ref' => '.ai/quality/fmea/full.md', 'control_plan' => 'docs/missing.md']]);
        self::assertStringContainsString('quality.control_plan must be an existing repository file', implode("\n", nexora_ai_control_plane_errors($this->fixture)));

        $this->write('docs/control.md', "# Control\n");
        $this->setUnit(['quality' => ['fmea_required' => true, 'fmea_ref' => '.ai/quality/fmea/full.md', 'control_plan' => 'docs/control.md']]);
        self::assertSame([], nexora_ai_control_plane_errors($this->fixture));
    }

    /** @param array<string,mixed> $research */
    private function setUnitResearch(array $research): void
    {
        $this->setUnit(['research' => $research]);
    }

    /** @param array<string,mixed> $extra */
    private function setUnit(array $extra, string $status = 'ACTIVE'): void
    {
        $this->writeJson('.ai/registry/development-units.json', ['units' => [
            array_merge(['id' => 'SYS-CORE-ADMIN-QA', 'status' => $status, 'parent_stage_id' => 'CORE-QA-001', 'depends_on' => ['RUNTIME-CLOSURE-001']], $extra),
        ]]);
    }

    private function brief(): string
    {
        return "# Brief\n\n## Problem\n\n## Users & VOC evidence\n\n## Baseline\n\nUNKNOWN\n\n## Alternatives\n\n## CTQs\n\n## Confidence\n\n## Sources\n";
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
