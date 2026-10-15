<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

require_once __DIR__.'/../../scripts/phase1-exposed-key-audit.php';

final class HistoricalKeyExposureAuditTest extends TestCase
{
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
}
