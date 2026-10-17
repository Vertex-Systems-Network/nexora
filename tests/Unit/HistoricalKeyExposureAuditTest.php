<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

require_once __DIR__.'/../../scripts/phase1-exposed-key-audit.php';

final class HistoricalKeyExposureAuditTest extends TestCase
{
    #[Test]
    public function rejects_historical_reference_files_inside_source_or_audited_target(): void
    {
        $sourceRoot = dirname(__DIR__, 2);
        $targetRoot = sys_get_temp_dir().'/nexora-reference-boundary-'.bin2hex(random_bytes(6));
        $externalRoot = sys_get_temp_dir().'/nexora-private-reference-'.bin2hex(random_bytes(6));
        $sourceReference = $sourceRoot.'/.nexora-synthetic-reference-'.bin2hex(random_bytes(6));
        $sourceAlias = $sourceRoot.'/.nexora-synthetic-alias-'.bin2hex(random_bytes(6));
        mkdir($targetRoot, 0700, true);
        mkdir($externalRoot, 0700, true);
        $targetReference = $targetRoot.'/synthetic-reference.key';
        $externalReference = $externalRoot.'/synthetic-reference.key';
        $synthetic = 'base64:'.base64_encode(str_repeat('Z', 32));

        try {
            foreach ([$sourceReference, $targetReference, $externalReference] as $file) {
                file_put_contents($file, $synthetic);
                chmod($file, 0600);
            }
            self::assertFalse(\nexoraHistoricalReferenceOutsideCheckouts($sourceReference, $targetRoot));
            self::assertFalse(\nexoraHistoricalReferenceOutsideCheckouts($targetReference, $targetRoot));
            self::assertTrue(\nexoraHistoricalReferenceOutsideCheckouts($externalReference, $targetRoot));
            if (function_exists('symlink') && @symlink($externalRoot, $sourceAlias)) {
                self::assertFalse(\nexoraHistoricalReferenceOutsideCheckouts(
                    $sourceAlias.'/synthetic-reference.key', $targetRoot
                ));
            }
            self::assertFalse(\nexoraHistoricalReferenceOutsideCheckouts($externalRoot.'/missing', $targetRoot));
            // Canonical containment must also detect references reached through
            // a source-root traversal or an alias (where permitted by the OS).
            self::assertFalse(\nexoraHistoricalReferenceOutsideCheckouts(
                $sourceRoot.'/tests/../'.basename($sourceReference), $targetRoot
            ));
        } finally {
            @unlink($sourceAlias);
            @unlink($sourceReference);
            @unlink($targetReference);
            @unlink($externalReference);
            @rmdir($targetRoot);
            @rmdir($externalRoot);
        }
    }

    #[Test]
    public function observes_exposed_key_in_active_previous_or_bootstrap_storage_without_printing_it(): void
    {
        $root = sys_get_temp_dir().'/nexora-history-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        mkdir($root.'/storage/app/nexora/environment', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $leaked = 'base64:'.base64_encode(str_repeat('L', 32));
        $safe = 'base64:'.base64_encode(str_repeat('S', 32));
        try {
            file_put_contents($root.'/.env', 'APP_KEY='.$safe."\nAPP_PREVIOUS_KEYS=\"{$leaked},{$safe}\"\n");
            file_put_contents($root.'/storage/app/nexora/environment/bootstrap.key', $safe);
            $result = \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root);
            self::assertSame('EXPOSED_KEY_OBSERVED', $result['status']);
            self::assertFalse($result['retired']);
            self::assertFalse($result['historical_non_use_proved']);
            self::assertStringNotContainsString($leaked, json_encode($result));
            file_put_contents($root.'/.env', 'APP_KEY='.$safe."\nAPP_PREVIOUS_KEYS={$safe}\n");
            file_put_contents($root.'/storage/app/nexora/environment/bootstrap.key', $leaked);
            self::assertSame('EXPOSED_KEY_OBSERVED', \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root)['status']);
            file_put_contents($root.'/storage/app/nexora/environment/bootstrap.key', $safe);
            self::assertSame('NO_MATCH_IN_SCANNED_FILES', \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root)['status']);
            mkdir($root.'/bootstrap/cache', 0700, true);
            file_put_contents($root.'/bootstrap/cache/config.php', '<?php return [];');
            self::assertSame('INCONCLUSIVE', \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root)['status']);
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }

    #[Test]
    public function missing_or_unreadable_active_marker_is_inconclusive_not_retired(): void
    {
        $root = sys_get_temp_dir().'/nexora-history-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        mkdir($root.'/storage/app/nexora/environment', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $safe = 'base64:'.base64_encode(str_repeat('S', 32));
        try {
            file_put_contents($root.'/.env', 'APP_KEY='.$safe."\n");
            file_put_contents($root.'/storage/app/nexora/environment/active', 'invalid');
            $result = \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root);
            self::assertSame('INCONCLUSIVE', $result['status']);
            self::assertGreaterThan(0, $result['unreadable']);
            self::assertFalse($result['target_verified']);
            self::assertNull(\nexoraHistoricalKeyBytes('not-a-laravel-key'));
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }

    #[Test]
    public function detects_exposed_cli_environment_key_even_when_stored_env_is_safe(): void
    {
        $root = sys_get_temp_dir().'/nexora-history-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $leaked = 'base64:'.base64_encode(str_repeat('L', 32));
        $safe = 'base64:'.base64_encode(str_repeat('S', 32));
        try {
            file_put_contents($root.'/.env', 'APP_KEY='.$safe."\n");
            $result = \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root, ['APP_KEY' => $leaked]);
            self::assertSame('EXPOSED_KEY_OBSERVED', $result['status']);
            self::assertSame(1, $result['process_variables_present']);
            self::assertSame(1, $result['process_key_slots_scanned']);
            self::assertFalse($result['retired']);
            self::assertFalse($result['target_verified']);
            self::assertFalse($result['historical_non_use_proved']);
            self::assertStringNotContainsString($leaked, json_encode($result, JSON_THROW_ON_ERROR));

            $result = \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root, [
                'APP_KEY' => $safe,
                'APP_PREVIOUS_KEYS' => '"'.$safe.','.$leaked.'"',
            ]);
            self::assertSame('EXPOSED_KEY_OBSERVED', $result['status']);
            self::assertSame(2, $result['process_variables_present']);
            self::assertSame(3, $result['process_key_slots_scanned']);
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }

    #[Test]
    public function invalid_process_environment_key_is_inconclusive_and_unrelated_variables_are_ignored(): void
    {
        $root = sys_get_temp_dir().'/nexora-history-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $leaked = 'base64:'.base64_encode(str_repeat('L', 32));
        $safe = 'base64:'.base64_encode(str_repeat('S', 32));
        try {
            file_put_contents($root.'/.env', 'APP_KEY='.$safe."\n");
            $invalid = \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root, ['APP_KEY' => 'malformed']);
            self::assertSame('INCONCLUSIVE', $invalid['status']);
            self::assertGreaterThan(0, $invalid['unreadable']);
            self::assertSame(0, $invalid['process_key_slots_scanned']);

            $ignored = \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root, ['OTHER_SECRET' => $leaked]);
            self::assertSame('NO_MATCH_IN_SCANNED_FILES', $ignored['status']);
            self::assertSame(0, $ignored['process_variables_present']);
            self::assertFalse($ignored['historical_non_use_proved']);
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }

    #[Test]
    public function detects_raw_32_byte_laravel_key_from_all_scanned_sources(): void
    {
        $root = sys_get_temp_dir().'/nexora-raw-key-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        mkdir($root.'/storage/app/nexora/environment', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $leaked = str_repeat('L', 32);
        $safe = 'base64:'.base64_encode(str_repeat('S', 32));
        try {
            file_put_contents($root.'/.env', 'APP_KEY='.$leaked."\n");
            self::assertSame('EXPOSED_KEY_OBSERVED', \nexoraHistoricalAuditTarget($leaked, $root)['status']);
            file_put_contents($root.'/.env', 'APP_KEY='.$safe."\n");
            file_put_contents($root.'/storage/app/nexora/environment/.env', 'APP_PREVIOUS_KEYS='.$leaked."\n");
            self::assertSame('EXPOSED_KEY_OBSERVED', \nexoraHistoricalAuditTarget($leaked, $root)['status']);
            file_put_contents($root.'/storage/app/nexora/environment/.env', 'APP_KEY='.$safe."\n");
            file_put_contents($root.'/storage/app/nexora/environment/bootstrap.key', $leaked);
            self::assertSame('EXPOSED_KEY_OBSERVED', \nexoraHistoricalAuditTarget($leaked, $root)['status']);
            file_put_contents($root.'/storage/app/nexora/environment/bootstrap.key', $safe);
            self::assertSame('EXPOSED_KEY_OBSERVED', \nexoraHistoricalAuditTarget($leaked, $root, ['APP_KEY' => $leaked])['status']);
            self::assertSame('EXPOSED_KEY_OBSERVED', \nexoraHistoricalAuditTarget($leaked, $root, ['APP_PREVIOUS_KEYS' => $leaked])['status']);
            self::assertNull(\nexoraHistoricalKeyBytes('short-not-a-key'));
            self::assertNull(\nexoraHistoricalKeyBytes('base64:invalid'));
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }

    #[Test]
    public function detects_exported_env_values_and_inline_comments_without_emitting_keys(): void
    {
        $root = sys_get_temp_dir().'/nexora-export-env-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $leaked = 'base64:'.base64_encode(str_repeat('L', 32));
        $safe = 'base64:'.base64_encode(str_repeat('S', 32));
        try {
            file_put_contents($root.'/.env', "export APP_KEY={$leaked} # operator note\n");
            $result = \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root);
            self::assertSame('EXPOSED_KEY_OBSERVED', $result['status']);
            self::assertStringNotContainsString($leaked, json_encode($result, JSON_THROW_ON_ERROR));
            file_put_contents($root.'/.env', "APP_KEY={$safe}\nexport APP_PREVIOUS_KEYS=\"{$safe},{$leaked}\" # prior keys\n");
            self::assertSame('EXPOSED_KEY_OBSERVED', \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root)['status']);
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }


    #[Test]
    public function detects_raw_app_key_containing_a_comma_without_splitting_it(): void
    {
        $root = sys_get_temp_dir().'/nexora-raw-comma-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $leaked = str_repeat('L', 15).','.str_repeat('L', 16);
        try {
            file_put_contents($root.'/.env', 'APP_KEY='.$leaked."\n");
            $result = \nexoraHistoricalAuditTarget($leaked, $root);
            self::assertSame('EXPOSED_KEY_OBSERVED', $result['status']);
            self::assertSame(1, $result['key_slots_scanned']);
            self::assertSame(0, $result['unreadable']);
            self::assertStringNotContainsString($leaked, json_encode($result, JSON_THROW_ON_ERROR));
            file_put_contents($root.'/.env', 'APP_KEY="'.$leaked.'"'."\n");
            self::assertSame('EXPOSED_KEY_OBSERVED', \nexoraHistoricalAuditTarget($leaked, $root)['status']);
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }

    #[Test]
    public function missing_app_key_in_selected_fallback_is_not_counted_as_complete_negative(): void
    {
        $root = sys_get_temp_dir().'/nexora-active-incomplete-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        mkdir($root.'/storage/app/nexora/environment', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $safe = 'base64:'.base64_encode(str_repeat('S', 32));
        try {
            file_put_contents($root.'/.env', 'APP_KEY='.$safe."\n");
            file_put_contents($root.'/storage/app/nexora/environment/.env', 'APP_PREVIOUS_KEYS='.$safe."\n");
            file_put_contents($root.'/storage/app/nexora/environment/active', 'fallback');
            $result = \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root);
            self::assertSame('INCONCLUSIVE', $result['status']);
            self::assertSame(0, $result['unreadable']);
            self::assertSame(true, $result['selected_environment_incomplete']);
            self::assertFalse($result['historical_non_use_proved']);
            file_put_contents($root.'/storage/app/nexora/environment/.env', 'APP_KEY='.$safe."\n");
            self::assertSame('NO_MATCH_IN_SCANNED_FILES', \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root)['status']);
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }

    #[Test]
    public function never_misclassifies_a_short_malformed_base64_prefix_as_raw_key(): void
    {
        // 32 textual bytes but not 32 decoded encryption-key bytes.
        $malformed = 'base64:'.str_repeat('A', 25);
        self::assertSame(32, strlen($malformed));
        self::assertNull(\nexoraHistoricalKeyBytes($malformed));
        self::assertNull(\nexoraHistoricalKeyBytes('"'.$malformed.'"'));
        self::assertSame(str_repeat('K', 32), \nexoraHistoricalKeyBytes('base64:'.base64_encode(str_repeat('K', 32))));
    }

    #[Test]
    public function preserves_all_whitespace_and_line_ending_raw_key_bytes_when_exactly_32_bytes(): void
    {
        $root = sys_get_temp_dir().'/nexora-raw-whitespace-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $spaceRaw = str_repeat(' ', 32);
        $newlineRaw = str_repeat('N', 31)."\n";
        try {
            self::assertSame($spaceRaw, \nexoraHistoricalKeyBytes($spaceRaw));
            self::assertSame($newlineRaw, \nexoraHistoricalKeyBytes($newlineRaw));
            file_put_contents($root.'/.env', 'APP_KEY="'.$spaceRaw.'"'."\n");
            self::assertSame('EXPOSED_KEY_OBSERVED', \nexoraHistoricalAuditTarget($spaceRaw, $root)['status']);
            file_put_contents($root.'/.env', 'APP_KEY='.('base64:'.base64_encode(str_repeat('S', 32)))."\n");
            self::assertSame('EXPOSED_KEY_OBSERVED', \nexoraHistoricalAuditTarget($spaceRaw, $root, ['APP_KEY' => $spaceRaw])['status']);
            self::assertSame('EXPOSED_KEY_OBSERVED', \nexoraHistoricalAuditTarget($newlineRaw, $root, ['APP_KEY' => $newlineRaw])['status']);
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }

    #[Test]
    public function detects_exact_raw_32_byte_keys_with_edge_spaces_and_quote_bytes(): void
    {
        $root = sys_get_temp_dir().'/nexora-raw-edge-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $raw = ' '.str_repeat('L', 30).' ';
        $quoteRaw = '"'.str_repeat('L', 30).'"';
        try {
            self::assertSame($raw, \nexoraHistoricalKeyBytes($raw));
            self::assertSame($raw, \nexoraHistoricalKeyBytes($raw."\n"));
            self::assertSame($quoteRaw, \nexoraHistoricalKeyBytes($quoteRaw));
            file_put_contents($root.'/.env', 'APP_KEY="'.$raw.'"'."\n");
            self::assertSame('EXPOSED_KEY_OBSERVED', \nexoraHistoricalAuditTarget($raw, $root)['status']);
            file_put_contents($root.'/.env', 'APP_KEY="'.$quoteRaw.'"'."\n");
            self::assertSame('EXPOSED_KEY_OBSERVED', \nexoraHistoricalAuditTarget($quoteRaw, $root)['status']);
            file_put_contents($root.'/.env', 'APP_KEY='.('base64:'.base64_encode(str_repeat('S',32)))."\n");
            self::assertSame('EXPOSED_KEY_OBSERVED', \nexoraHistoricalAuditTarget($raw, $root, ['APP_KEY' => $raw])['status']);
            self::assertSame('EXPOSED_KEY_OBSERVED', \nexoraHistoricalAuditTarget($quoteRaw, $root, ['APP_KEY' => $quoteRaw])['status']);
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }



    #[Test]
    public function automatic_environment_missing_current_key_cannot_return_scoped_negative(): void
    {
        $root = sys_get_temp_dir().'/nexora-auto-current-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        mkdir($root.'/storage/app/nexora/environment', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $safe = 'base64:'.base64_encode(str_repeat('S', 32));
        try {
            file_put_contents($root.'/.env', 'APP_PREVIOUS_KEYS='.$safe."\n");
            // Even a safe inactive fallback APP_KEY is not a current root key.
            file_put_contents($root.'/storage/app/nexora/environment/.env', 'APP_KEY='.$safe."\n");
            $result = \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root);
            self::assertSame('INCONCLUSIVE', $result['status']);
            self::assertTrue($result['selected_environment_incomplete']);
            self::assertSame(0, $result['unreadable']);
            unlink($root.'/.env');
            self::assertSame('NO_MATCH_IN_SCANNED_FILES', \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root)['status']);
            file_put_contents($root.'/storage/app/nexora/environment/.env', 'APP_PREVIOUS_KEYS='.$safe."\n");
            self::assertSame('INCONCLUSIVE', \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root)['status']);
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }

    #[Test]
    public function bootstrap_or_process_current_key_counts_as_scanned_source_not_web_proof(): void
    {
        $root = sys_get_temp_dir().'/nexora-auto-bootstrap-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        mkdir($root.'/storage/app/nexora/environment', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $safe = 'base64:'.base64_encode(str_repeat('S', 32));
        try {
            file_put_contents($root.'/.env', 'APP_PREVIOUS_KEYS='.$safe."\n");
            $result = \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root, ['APP_KEY' => $safe]);
            self::assertSame('NO_MATCH_IN_SCANNED_FILES', $result['status']);
            self::assertFalse($result['target_verified']);
            self::assertFalse($result['retired']);
            file_put_contents($root.'/storage/app/nexora/environment/bootstrap.key', $safe);
            $result = \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root);
            self::assertSame('NO_MATCH_IN_SCANNED_FILES', $result['status']);
            self::assertFalse($result['historical_non_use_proved']);
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }

    #[Test]
    public function oversized_secret_source_is_inconclusive_even_with_other_safe_keys(): void
    {
        $root = sys_get_temp_dir().'/nexora-audit-bounds-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        mkdir($root.'/storage/app/nexora/environment', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $safe = 'base64:'.base64_encode(str_repeat('S', 32));
        try {
            file_put_contents($root.'/.env', 'APP_KEY='.$safe."\n");
            $candidate = $root.'/storage/app/nexora/environment/bootstrap.key';
            file_put_contents($candidate, str_repeat('x', NEXORA_HISTORICAL_MAX_KEY_BYTES + 1));
            $result = \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root);
            self::assertSame('INCONCLUSIVE', $result['status']);
            self::assertGreaterThan(0, $result['unreadable']);
            unlink($candidate);
            file_put_contents($root.'/.env', str_repeat('a', NEXORA_HISTORICAL_MAX_ENV_BYTES + 1));
            $result = \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root, ['APP_KEY' => $safe]);
            self::assertSame('INCONCLUSIVE', $result['status']);
            self::assertGreaterThan(0, $result['unreadable']);
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }

    #[Test]
    public function protected_environment_directory_symlink_never_produces_a_scoped_negative(): void
    {
        $root = sys_get_temp_dir().'/nexora-linked-parent-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        mkdir($root.'/storage/app/nexora', 0700, true);
        mkdir($root.'/external-environment', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $safe = 'base64:'.base64_encode(str_repeat('S', 32));
        try {
            file_put_contents($root.'/.env', 'APP_KEY='.$safe."\n");
            file_put_contents($root.'/external-environment/.env', 'APP_KEY='.$safe."\n");
            file_put_contents($root.'/external-environment/active', 'fallback');
            if (! function_exists('symlink') || ! @symlink($root.'/external-environment', $root.'/storage/app/nexora/environment')) {
                self::assertTrue(true); // Windows environments may forbid symlinks.
                return;
            }
            $result = \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root);
            self::assertSame('INCONCLUSIVE', $result['status']);
            self::assertGreaterThan(0, $result['unreadable']);
            self::assertFalse($result['target_verified']);
            self::assertFalse($result['retired']);
            // An exposed key in an external symlink target must not be consumed
            // as if it were a trusted file below the selected checkout.
            file_put_contents($root.'/external-environment/.env', 'APP_KEY='.('base64:'.base64_encode(str_repeat('L', 32)))."\n");
            self::assertSame('INCONCLUSIVE', \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root)['status']);
        } finally {
            $link = $root.'/storage/app/nexora/environment';
            if (is_link($link)) {
                unlink($link);
            }
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }
    #[Test]
    public function independent_cli_environment_apis_are_all_scanned_when_their_keys_disagree(): void
    {
        $root = sys_get_temp_dir().'/nexora-env-variants-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $safe = 'base64:'.base64_encode(str_repeat('S', 32));
        $exposed = 'base64:'.base64_encode(str_repeat('X', 32));
        file_put_contents($root.'/.env', 'APP_KEY='.$safe."\n");
        $previousGetenv = getenv('APP_KEY');
        $previousEnv = $_ENV['APP_KEY'] ?? null;
        $previousServer = $_SERVER['APP_KEY'] ?? null;
        $previousEnvHistory = $_ENV['APP_PREVIOUS_KEYS'] ?? null;
        $previousGetenvHistory = getenv('APP_PREVIOUS_KEYS');
        try {
            putenv('APP_KEY='.$safe);
            putenv('APP_PREVIOUS_KEYS');
            $_ENV['APP_KEY'] = $exposed;
            $_SERVER['APP_KEY'] = $safe;
            $_ENV['APP_PREVIOUS_KEYS'] = $safe;
            $settings = \nexoraHistoricalReadProcessSettings();
            self::assertTrue(is_array($settings['APP_KEY']));
            self::assertSame(2, count($settings['APP_KEY']));
            $result = \nexoraHistoricalAuditTarget(str_repeat('X', 32), $root, $settings);
            self::assertSame('EXPOSED_KEY_OBSERVED', $result['status']);
            self::assertStringNotContainsString($exposed, json_encode($result, JSON_THROW_ON_ERROR));
            self::assertFalse($result['retired']);
            $_ENV['APP_KEY'] = $safe;
            $_ENV['APP_PREVIOUS_KEYS'] = $exposed;
            $result = \nexoraHistoricalAuditTarget(str_repeat('X', 32), $root, \nexoraHistoricalReadProcessSettings());
            self::assertSame('EXPOSED_KEY_OBSERVED', $result['status']);
        } finally {
            $previousGetenv === false ? putenv('APP_KEY') : putenv('APP_KEY='.$previousGetenv);
            $previousGetenvHistory === false ? putenv('APP_PREVIOUS_KEYS') : putenv('APP_PREVIOUS_KEYS='.$previousGetenvHistory);
            if ($previousEnv === null) { unset($_ENV['APP_KEY']); } else { $_ENV['APP_KEY'] = $previousEnv; }
            if ($previousServer === null) { unset($_SERVER['APP_KEY']); } else { $_SERVER['APP_KEY'] = $previousServer; }
            if ($previousEnvHistory === null) { unset($_ENV['APP_PREVIOUS_KEYS']); } else { $_ENV['APP_PREVIOUS_KEYS'] = $previousEnvHistory; }
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }

    #[Test]
    public function malformed_or_oversized_parallel_cli_sources_fail_closed(): void
    {
        $root = sys_get_temp_dir().'/nexora-env-invalid-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $safe = 'base64:'.base64_encode(str_repeat('S', 32));
        file_put_contents($root.'/.env', 'APP_KEY='.$safe."\n");
        try {
            foreach ([[$safe, 'not-a-key'], [$safe, str_repeat('Z', NEXORA_HISTORICAL_MAX_ENV_BYTES + 1)], [$safe, null]] as $inputs) {
                $result = \nexoraHistoricalAuditTarget(str_repeat('X', 32), $root, ['APP_KEY' => $inputs]);
                self::assertSame('INCONCLUSIVE', $result['status']);
                self::assertGreaterThan(0, $result['unreadable']);
                self::assertFalse($result['target_verified']);
                self::assertFalse($result['historical_non_use_proved']);
            }
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }



    #[Test]
    public function detects_quoted_escaped_raw_key_using_installer_environment_decoding(): void
    {
        $root = sys_get_temp_dir().'/nexora-escaped-current-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $leaked = str_repeat('L', 10).'"'.str_repeat('L', 10).'\\'.str_repeat('L', 10);
        self::assertSame(32, strlen($leaked));
        $encoded = str_replace(['\\', '"'], ['\\\\', '\\"'], $leaked);
        try {
            file_put_contents($root.'/.env', 'APP_KEY="'.$encoded.'"' . "\n");
            $result = \nexoraHistoricalAuditTarget($leaked, $root);
            self::assertSame('EXPOSED_KEY_OBSERVED', $result['status']);
            self::assertStringNotContainsString($leaked, json_encode($result, JSON_THROW_ON_ERROR));
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }

    #[Test]
    public function detects_quoted_escaped_newline_raw_key(): void
    {
        $root = sys_get_temp_dir().'/nexora-escaped-newline-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $leaked = str_repeat('N', 15)."\n".str_repeat('N', 16);
        self::assertSame(32, strlen($leaked));
        try {
            file_put_contents($root.'/.env', 'APP_KEY="'.str_repeat('N', 15).'\\n'.str_repeat('N', 16).'"' . "\n");
            $result = \nexoraHistoricalAuditTarget($leaked, $root);
            self::assertSame('EXPOSED_KEY_OBSERVED', $result['status']);
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }

    #[Test]
    public function detects_escaped_previous_keys_without_confusing_comma_separation(): void
    {
        $root = sys_get_temp_dir().'/nexora-escaped-previous-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $leaked = str_repeat('P', 15).'\\'.str_repeat('P', 16);
        $safe = 'base64:'.base64_encode(str_repeat('S', 32));
        self::assertSame(32, strlen($leaked));
        try {
            file_put_contents($root.'/.env', 'APP_KEY='.$safe."\n".'APP_PREVIOUS_KEYS="'.$safe.','.str_repeat('P', 15).'\\\\'.str_repeat('P', 16).'"'."\n");
            $result = \nexoraHistoricalAuditTarget($leaked, $root);
            self::assertSame('EXPOSED_KEY_OBSERVED', $result['status']);
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }
    #[Test]
    public function duplicate_app_key_assignments_with_empty_last_value_cannot_produce_scoped_negative(): void
    {
        $root = sys_get_temp_dir().'/nexora-history-duplicate-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $safe = 'base64:'.base64_encode(str_repeat('S', 32));
        $leaked = 'base64:'.base64_encode(str_repeat('L', 32));
        try {
            // Nexora's bootstrap parser uses the LAST APP_KEY assignment.
            // A previous safe value must not certify this environment.
            file_put_contents($root.'/.env', 'APP_KEY='.$safe."\nAPP_KEY=\n");
            $result = \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root);
            self::assertSame('INCONCLUSIVE', $result['status']);
            self::assertTrue($result['selected_environment_incomplete']);
            self::assertFalse($result['historical_non_use_proved']);

            // Preserve detection of the exposed key in ANY duplicate slot.
            file_put_contents($root.'/.env', 'APP_KEY='.$leaked."\nAPP_KEY=\n");
            self::assertSame('EXPOSED_KEY_OBSERVED', \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root)['status']);
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }

    #[Test]
    public function duplicate_previous_key_assignments_are_ambiguous_even_when_non_matching(): void
    {
        $root = sys_get_temp_dir().'/nexora-history-duplicate-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $safe = 'base64:'.base64_encode(str_repeat('S', 32));
        $leaked = 'base64:'.base64_encode(str_repeat('L', 32));
        try {
            file_put_contents($root.'/.env', 'APP_KEY='.$safe."\nAPP_PREVIOUS_KEYS={$safe}\nAPP_PREVIOUS_KEYS={$safe}\n");
            $result = \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root);
            self::assertSame('INCONCLUSIVE', $result['status']);
            self::assertGreaterThan(0, $result['duplicate_assignments']);
            file_put_contents($root.'/.env', 'APP_KEY='.$safe."\nAPP_PREVIOUS_KEYS={$leaked}\nAPP_PREVIOUS_KEYS={$safe}\n");
            self::assertSame('EXPOSED_KEY_OBSERVED', \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root)['status']);
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }


    #[Test]
    public function explicitly_empty_cli_app_key_is_inconclusive_even_when_disk_key_looks_safe(): void
    {
        $root = sys_get_temp_dir().'/nexora-empty-cli-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $safe = 'base64:'.base64_encode(str_repeat('S', 32));
        $exposed = 'base64:'.base64_encode(str_repeat('X', 32));
        try {
            file_put_contents($root.'/.env', 'APP_KEY='.$safe."\n");
            $result = \nexoraHistoricalAuditTarget(str_repeat('X', 32), $root, ['APP_KEY' => '']);
            self::assertSame('INCONCLUSIVE', $result['status']);
            self::assertGreaterThan(0, $result['unreadable']);
            self::assertSame(1, $result['process_variables_present']);
            self::assertFalse($result['retired']);
            // Any positive observed key still outranks an ambiguous blank slot.
            $positive = \nexoraHistoricalAuditTarget(str_repeat('X', 32), $root, ['APP_KEY' => ['', $exposed]]);
            self::assertSame('EXPOSED_KEY_OBSERVED', $positive['status']);
            self::assertStringNotContainsString($exposed, json_encode($positive, JSON_THROW_ON_ERROR));
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }

    #[Test]
    public function process_superglobal_empty_app_key_is_preserved_as_ambiguous_source(): void
    {
        $root = sys_get_temp_dir().'/nexora-empty-env-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $safe = 'base64:'.base64_encode(str_repeat('S', 32));
        $priorGetenv = getenv('APP_KEY');
        $hadEnv = array_key_exists('APP_KEY', $_ENV);
        $priorEnv = $_ENV['APP_KEY'] ?? null;
        $hadServer = array_key_exists('APP_KEY', $_SERVER);
        $priorServer = $_SERVER['APP_KEY'] ?? null;
        try {
            file_put_contents($root.'/.env', 'APP_KEY='.$safe."\n");
            putenv('APP_KEY='.$safe);
            $_ENV['APP_KEY'] = '';
            $_SERVER['APP_KEY'] = $safe;
            $settings = \nexoraHistoricalReadProcessSettings();
            self::assertTrue(is_array($settings['APP_KEY']));
            self::assertSame(2, count($settings['APP_KEY']));
            $result = \nexoraHistoricalAuditTarget(str_repeat('X', 32), $root, $settings);
            self::assertSame('INCONCLUSIVE', $result['status']);
            self::assertGreaterThan(0, $result['unreadable']);
            self::assertSame(1, $result['process_variables_present']);
            // An unset source is not the same as a deliberately empty source.
            unset($_ENV['APP_KEY']);
            $notEmpty = \nexoraHistoricalAuditTarget(str_repeat('X', 32), $root, \nexoraHistoricalReadProcessSettings());
            self::assertSame('NO_MATCH_IN_SCANNED_FILES', $notEmpty['status']);
        } finally {
            $priorGetenv === false ? putenv('APP_KEY') : putenv('APP_KEY='.$priorGetenv);
            if ($hadEnv) { $_ENV['APP_KEY'] = $priorEnv; } else { unset($_ENV['APP_KEY']); }
            if ($hadServer) { $_SERVER['APP_KEY'] = $priorServer; } else { unset($_SERVER['APP_KEY']); }
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }


    #[Test]
    public function unquoted_raw_key_with_space_hash_must_be_scanned_as_actual_bootstrap_value(): void
    {
        $root = sys_get_temp_dir().'/nexora-unquoted-hash-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        // The installer bootstrap trims an unquoted value; it does not remove
        // inline comments. This complete value is a usable raw 32-byte key.
        $raw = str_repeat('X', 28).' #YZ';
        self::assertSame(32, strlen($raw));
        try {
            file_put_contents($root.'/.env', "APP_KEY={$raw}\n");
            $result = \nexoraHistoricalAuditTarget($raw, $root);
            self::assertSame('EXPOSED_KEY_OBSERVED', $result['status']);
            self::assertFalse($result['retired']);
            self::assertStringNotContainsString($raw, json_encode($result, JSON_THROW_ON_ERROR));
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }

    #[Test]
    public function ambiguous_unquoted_inline_comment_must_not_be_a_scoped_clean_bill_of_health(): void
    {
        $root = sys_get_temp_dir().'/nexora-unquoted-comment-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        $safe = 'base64:'.base64_encode(str_repeat('S', 32));
        try {
            file_put_contents($root.'/.env', "APP_KEY={$safe} # not necessarily discarded by pre-Laravel bootstrap\n");
            $result = \nexoraHistoricalAuditTarget(str_repeat('X', 32), $root);
            self::assertSame('INCONCLUSIVE', $result['status']);
            self::assertFalse($result['historical_non_use_proved']);
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }


    #[Test]
    public function public_audit_api_rejects_invalid_reference_key_lengths_before_reporting_no_match(): void
    {
        $root = sys_get_temp_dir().'/nexora-invalid-ref-'.bin2hex(random_bytes(7));
        mkdir($root.'/bootstrap', 0700, true);
        file_put_contents($root.'/artisan', '<?php');
        file_put_contents($root.'/bootstrap/app.php', '<?php');
        file_put_contents($root.'/.env', 'APP_KEY=base64:'.base64_encode(str_repeat('S', 32))."\n");
        try {
            foreach ([str_repeat('L', 31), str_repeat('L', 33), 'base64:'.base64_encode(str_repeat('L', 32)), ''] as $invalid) {
                $rejected = false;
                try {
                    \nexoraHistoricalAuditTarget($invalid, $root);
                } catch (\InvalidArgumentException $e) {
                    $rejected = true;
                    if ($invalid !== '') {
                        self::assertStringNotContainsString($invalid, $e->getMessage());
                    }
                }
                self::assertTrue($rejected);
            }
            // A valid 32-byte reference must still perform the normal audit.
            self::assertSame('NO_MATCH_IN_SCANNED_FILES', \nexoraHistoricalAuditTarget(str_repeat('L', 32), $root)['status']);
            self::assertSame('EXPOSED_KEY_OBSERVED', \nexoraHistoricalAuditTarget(str_repeat('S', 32), $root)['status']);
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($root);
        }
    }

}
