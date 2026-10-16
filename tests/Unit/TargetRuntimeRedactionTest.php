<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../scripts/lib/target-runtime-redaction.php';

final class TargetRuntimeRedactionTest extends TestCase
{
    #[Test]
    public function app_key_base64_never_survives_logs(): void
    {
        $out = \nexoraTargetRedactOutput("APP_KEY=base64:SYNTHETIC_PASSWORD_123\n");
        self::assertSame("APP_KEY=[REDACTED]\n", $out);
    }

    #[Test]
    public function json_app_key_is_masked_without_corrupting_structure(): void
    {
        $out = \nexoraTargetRedactOutput('{"APP_KEY": "base64:FAKE_VALUE", "status": "ready"}');
        self::assertSame('{"APP_KEY": "[REDACTED]", "status": "ready"}', $out);
    }

    #[Test]
    public function database_redis_and_mail_uris_are_redacted(): void
    {
        foreach (['DATABASE_URL', 'REDIS_URL', 'MAIL_URL'] as $key) {
            $out = \nexoraTargetRedactOutput("{$key}=postgres://user:synthetic_pw@localhost/db");
            self::assertSame($key.'=[REDACTED]', $out);
        }
    }

    #[Test]
    public function cloud_provider_secret_fields_are_redacted(): void
    {
        foreach (['AWS_SECRET_ACCESS_KEY', 'AWS_ACCESS_KEY_ID', 'AWS_SESSION_TOKEN', 'STRIPE_SECRET_KEY', 'NEXORA_SIGNING_TOKEN', 'DB_PASSWORD'] as $key) {
            $out = \nexoraTargetRedactOutput($key.'=SYNTHETIC_SECRET_TEST_77');
            self::assertSame($key.'=[REDACTED]', $out);
        }
    }

    #[Test]
    public function http_authorization_headers_are_entirely_scrubbed(): void
    {
        self::assertSame('Authorization: [REDACTED]', \nexoraTargetRedactOutput('Authorization: Bearer synthetic-token'));
        self::assertSame('Proxy-Authorization: [REDACTED]', \nexoraTargetRedactOutput('Proxy-Authorization: Basic ZmFrZTpwYXNz'));
    }

    #[Test]
    public function basic_and_bearer_tokens_are_hidden_without_header(): void
    {
        self::assertSame('Bearer [REDACTED]', \nexoraTargetRedactOutput('Bearer synthetic.jwt.value'));
        self::assertSame('Basic [REDACTED]', \nexoraTargetRedactOutput('Basic ZmFrZTpwYXNz'));
    }

    #[Test]
    public function secret_query_params_are_masked_without_dropping_safe_flags(): void
    {
        $out = \nexoraTargetRedactOutput('https://example.test/auth?token=FAKEJWT&mode=dev&api_key=FAKEAPI');
        self::assertSame('https://example.test/auth?token=[REDACTED]&mode=dev&api_key=[REDACTED]', $out);
    }

    #[Test]
    public function postgres_dsn_password_is_hidden(): void
    {
        $out = \nexoraTargetRedactOutput('connected to postgres://service:FAKEPASS@db.example.test/main');
        self::assertSame('connected to postgres://service:[REDACTED]@db.example.test/main', $out);
    }

    #[Test]
    public function multiline_private_key_block_never_survives(): void
    {
        $out = \nexoraTargetRedactOutput("log\n-----BEGIN PRIVATE KEY-----\nFAKEKEYLINE\n-----END PRIVATE KEY-----\nnext");
        self::assertSame("log\n[REDACTED PRIVATE KEY]\nnext", $out);
    }

    #[Test]
    public function openssh_private_key_block_is_scrubbed(): void
    {
        $out = \nexoraTargetRedactOutput("-----BEGIN OPENSSH PRIVATE KEY-----\nFAKE\n-----END OPENSSH PRIVATE KEY-----");
        self::assertSame('[REDACTED PRIVATE KEY]', $out);
    }

    #[Test]
    public function safe_diagnostics_remain_readable(): void
    {
        $input = "Vite build passed; 38 assets generated\nstatus: ready\nversion=2.9.1\n";
        self::assertSame($input, \nexoraTargetRedactOutput($input));
    }

    #[Test]
    public function redacting_the_same_text_twice_is_idempotent(): void
    {
        $once = \nexoraTargetRedactOutput('APP_KEY=base64:FAKE Authorization: Bearer FAKEJWT');
        self::assertSame($once, \nexoraTargetRedactOutput($once));
    }

    #[Test]
    public function split_credential_command_arguments_are_redacted(): void
    {
        $parts = ['php', 'artisan', '--token', 'SYNTHETIC_SECRET_TEST_88', '--verbose'];
        self::assertSame(['php', 'artisan', '--token', '[REDACTED]', '--verbose'],
            \nexoraTargetRedactCommandParts($parts));
        // The real process argument list is not changed by redaction.
        self::assertSame('SYNTHETIC_SECRET_TEST_88', $parts[3]);
    }

    #[Test]
    public function equals_style_credential_arguments_are_redacted(): void
    {
        self::assertSame(['--api-key=[REDACTED]', '--mode=release'],
            \nexoraTargetRedactCommandParts(['--api-key=FAKE_KEY', '--mode=release']));
    }
}
