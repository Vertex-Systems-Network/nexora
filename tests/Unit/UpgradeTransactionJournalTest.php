<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Foundation\Upgrade\UpgradeTransactionJournal;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class UpgradeTransactionJournalTest extends TestCase
{
    #[Test]
    public function protected_upgrade_transaction_is_checkpointed_and_tamper_evident(): void
    {
        $path=storage_path('framework/testing-nexora-upgrade-transaction.json');
        $history=storage_path('framework/testing-nexora-upgrade-transactions');
        @unlink($path);if(is_dir($history)){foreach(glob($history.'/*')?:[] as $file)@unlink($file);@rmdir($history);}
        config()->set('nexora-upgrade.transaction_journal_path',$path);
        config()->set('nexora-upgrade.transaction_history_path',$history);
        $journal=app(UpgradeTransactionJournal::class);
        $written=$journal->begin(['upgrade_id'=>'upgrade-journal-1','source_version'=>'1.0.0-rc.42','target_version'=>'1.0.0-rc.43','trusted_update_receipt_sha256'=>str_repeat('a',64),'backup_type'=>'external','backup_reference'=>'backup.json','backup_sha256'=>str_repeat('b',64)]);
        self::assertSame('running',$written['status']??null);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/',(string)($written['journal_sha256']??''));
        $journal->checkpoint('maintenance_enabled');$journal->checkpoint('migrations_started');$failed=$journal->fail('migrations_started','simulated failure');
        self::assertSame('recovery_required',$failed['status']??null);self::assertTrue((bool)($failed['maintenance_required']??false));

        $tampered=(array)json_decode((string)file_get_contents($path),true);$tampered['stage']='tampered';file_put_contents($path,json_encode($tampered,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        $this->expectException(RuntimeException::class);
        try{$journal->read();}finally{@unlink($path);if(is_dir($history)){foreach(glob($history.'/*')?:[] as $file)@unlink($file);@rmdir($history);}}
    }
}
