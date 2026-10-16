<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../scripts/lib/target-runtime-redaction.php';

final class TargetRuntimePreviousKeysRedactionTest extends TestCase
{
    #[Test]
    public function comma_separated_previous_encryption_keys_are_fully_masked(): void
    {
        $out = \nexoraTargetRedactOutput('APP_PREVIOUS_KEYS=base64:SYNTHETIC_FIRST,base64:SYNTHETIC_SECOND');
        self::assertSame('APP_PREVIOUS_KEYS=[REDACTED]', $out);
    }

    #[Test]
    public function quoted_previous_keys_remain_quoted_and_fully_masked(): void
    {
        $out = \nexoraTargetRedactOutput('APP_PREVIOUS_KEYS="base64:FAKE_A,base64:FAKE_B"');
        self::assertSame('APP_PREVIOUS_KEYS="[REDACTED]"', $out);
    }

    #[Test]
    public function json_array_of_previous_keys_preserves_valid_json_shape(): void
    {
        $out = \nexoraTargetRedactOutput('{"APP_PREVIOUS_KEYS": ["base64:FAKE_A", "base64:FAKE_B"], "status": "ready"}');
        self::assertSame(['APP_PREVIOUS_KEYS'=>['[REDACTED]'], 'status'=>'ready'], json_decode($out,true,512,JSON_THROW_ON_ERROR));
    }

    #[Test]
    public function json_string_of_previous_keys_is_masked(): void
    {
        $out = \nexoraTargetRedactOutput('{"APP_PREVIOUS_KEYS": "base64:FAKE_A,base64:FAKE_B"}');
        self::assertSame(['APP_PREVIOUS_KEYS'=>'[REDACTED]'], json_decode($out,true,512,JSON_THROW_ON_ERROR));
    }

    #[Test]
    public function split_previous_keys_command_switch_masks_entire_value(): void
    {
        $original=['php','artisan','--app-previous-keys','base64:FAKE_A,base64:FAKE_B','--verbose'];
        self::assertSame(['php','artisan','--app-previous-keys','[REDACTED]','--verbose'], \nexoraTargetRedactCommandParts($original));
        self::assertSame('base64:FAKE_A,base64:FAKE_B',$original[3]);
    }

    #[Test]
    public function equals_style_previous_keys_command_switch_masks_entire_value(): void
    {
        self::assertSame(['--app_previous_keys=[REDACTED]', '--mode=review'],
            \nexoraTargetRedactCommandParts(['--app_previous_keys=base64:FAKE_A,base64:FAKE_B','--mode=review']));
    }

    #[Test]
    public function private_key_command_argument_is_also_masked(): void
    {
        self::assertSame(['--private-key', '[REDACTED]'],
            \nexoraTargetRedactCommandParts(['--private-key', 'FAKE_PRIVATE_KEY_MATERIAL']));
    }

    #[Test]
    public function masking_previous_keys_twice_is_stable_and_nonsecret_status_survives(): void
    {
        $once=\nexoraTargetRedactOutput('APP_PREVIOUS_KEYS=base64:FAKE_A,base64:FAKE_B status=pass');
        self::assertSame($once,\nexoraTargetRedactOutput($once));
        self::assertSame('APP_PREVIOUS_KEYS=[REDACTED] status=pass', $once);
    }
}
