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
}
