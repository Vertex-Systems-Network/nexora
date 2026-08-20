<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Transfers;

final class TransferDoctor
{
    public function __construct(private readonly TransferSafety $transfers) {}

    /** @return array{status:string,checks:list<array{key:string,status:string,detail:string>>,temporary_root:string} */
    public function inspect(bool $probe = true): array
    {
        $checks = []; $failed = false;
        try {
            $root = $this->transfers->temporaryRoot();
            $this->transfers->assertLocalCapacity($root, 0);
            $checks[] = ['key'=>'temporary-root','status'=>'pass','detail'=>$root];
        } catch (\Throwable $e) {
            return ['status'=>'fail','checks'=>[['key'=>'temporary-root','status'=>'fail','detail'=>$e->getMessage()]],'temporary_root'=>''];
        }

        if ($probe) {
            $source = $this->transfers->temporaryPath('doctor-source', '.bin');
            $destination = $this->transfers->temporaryPath('doctor-destination', '.bin');
            try {
                $payload = random_bytes(262_144);
                if (file_put_contents($source, $payload, LOCK_EX) !== strlen($payload)) throw new \RuntimeException('Unable to create transfer doctor source payload.');
                $copied = $this->transfers->copyFileAtomically($source, $destination, 1_048_576);
                $ok = $copied['bytes'] === strlen($payload) && hash_equals(hash('sha256',$payload), $copied['sha256']) && is_file($destination);
                if (! $ok) throw new \RuntimeException('Bounded atomic transfer probe did not round-trip.');
                $checks[] = ['key'=>'bounded-copy','status'=>'pass','detail'=>'256 KiB bounded copy + SHA-256 + atomic publish passed'];
            } catch (\Throwable $e) {
                $failed = true;
                $checks[] = ['key'=>'bounded-copy','status'=>'fail','detail'=>$e->getMessage()];
            } finally {
                @unlink($source); @unlink($destination);
            }
        }

        foreach (['theme','extension'] as $type) {
            $budget=(array)config("nexora-transfers.archives.{$type}",[]);
            $ok=(int)($budget['max_entries']??0)>0 && (int)($budget['max_total_uncompressed_bytes']??0)>0 && (int)($budget['max_entry_uncompressed_bytes']??0)>0;
            $failed=$failed||!$ok;
            $checks[]=['key'=>'archive-budget:'.$type,'status'=>$ok?'pass':'fail','detail'=>$ok?'bounded entries/uncompressed bytes/compression ratio configured':'archive budget is incomplete'];
        }

        return ['status'=>$failed?'fail':'pass','checks'=>$checks,'temporary_root'=>$root];
    }
}
