<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Foundation\Runtime\FreshInstallDependencyTrust;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class FreshInstallDependencyReceiptCommitTest extends TestCase
{
    #[Test]
    public function bootstrap_receipt_requires_valid_integrity_before_publication(): void
    {
        $path = storage_path('framework/testing-fresh-install-bootstrap.json');
        @unlink($path);
        config()->set('nexora-framework.fresh_install_dependency_trust.receipt_path', $path);
        $trust = app(FreshInstallDependencyTrust::class);

        $unsigned = [
            'schema' => 1,
            'status' => 'verified',
            'trust_mode' => 'fresh-install-bootstrap',
        ];
        ksort($unsigned, SORT_STRING);
        $receipt = [
            ...$unsigned,
            'receipt_sha256' => hash('sha256', json_encode($unsigned, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
        ];

        try {
            $trust->commitBootstrapReceipt($receipt);
            self::assertFileExists($path);

            $trust->discardOrphanedBootstrapReceipt();
            self::assertFileDoesNotExist($path);

            $receipt['receipt_sha256'] = str_repeat('0', 64);
            $this->expectException(RuntimeException::class);
            $trust->commitBootstrapReceipt($receipt);
        } finally {
            @unlink($path);
        }
    }
}
