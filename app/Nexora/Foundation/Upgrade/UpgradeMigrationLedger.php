<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Upgrade;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class UpgradeMigrationLedger
{
    /** @return array<string,mixed> */
    public function snapshot(): array
    {
        $sourceFiles=array_map(static fn(string $path): string=>pathinfo($path,PATHINFO_FILENAME),glob(database_path('migrations/*.php'))?:[]);
        sort($sourceFiles,SORT_STRING);
        $rows=[];
        if(Schema::hasTable('migrations')){
            foreach(DB::table('migrations')->select(['migration','batch'])->orderBy('migration')->get() as $row){
                $rows[]=['migration'=>(string)$row->migration,'batch'=>(int)$row->batch];
            }
        }
        $applied=array_map(static fn(array $row):string=>$row['migration'],$rows);
        $duplicates=array_values(array_keys(array_filter(array_count_values($applied),static fn(int $count):bool=>$count>1)));
        $pending=array_values(array_diff($sourceFiles,$applied));sort($pending,SORT_STRING);sort($duplicates,SORT_STRING);
        $payload=[
            'schema'=>1,
            'connection'=>(string)config('database.default',''),
            'driver'=>(string)DB::connection()->getDriverName(),
            'source_migrations'=>$sourceFiles,
            'applied'=>$rows,
            'pending'=>$pending,
            'duplicates'=>$duplicates,
        ];
        $payload['ledger_sha256']=$this->hash($payload);
        return $payload;
    }

    /** @param array<string,mixed> $planned */
    public function assertUnchanged(array $planned): array
    {
        $current=$this->snapshot();
        $expected=strtolower(trim((string)($planned['ledger_sha256']??'')));
        if($expected===''||!hash_equals($expected,(string)$current['ledger_sha256'])){
            throw new \RuntimeException('Migration ledger changed after the upgrade plan was sealed; create a new plan before applying migrations.');
        }
        return $current;
    }

    /** @param array<string,mixed> $before @return array<string,mixed> */
    public function assertConverged(array $before): array
    {
        $after=$this->snapshot();$errors=[];
        if(($after['duplicates']??[])!==[])$errors[]='duplicate migration ledger entries detected';
        if(($after['pending']??[])!==[])$errors[]='source migrations remain pending: '.implode(',',(array)$after['pending']);
        $beforeApplied=array_map(static fn(array $row):string=>(string)($row['migration']??''),(array)($before['applied']??[]));
        $afterApplied=array_map(static fn(array $row):string=>(string)($row['migration']??''),(array)($after['applied']??[]));
        $missing=array_values(array_diff($beforeApplied,$afterApplied));
        if($missing!==[])$errors[]='previously applied migrations disappeared: '.implode(',',$missing);
        if($errors!==[])throw new \RuntimeException('Post-upgrade migration ledger did not converge: '.implode('; ',$errors));
        $after['status']='pass';$after['previous_ledger_sha256']=$before['ledger_sha256']??null;
        $after['convergence_sha256']=$this->hash($after);
        return $after;
    }

    /** @param array<string,mixed> $payload */
    private function hash(array $payload): string
    {
        unset($payload['ledger_sha256'],$payload['convergence_sha256']);
        $payload=$this->canonicalize($payload);
        return hash('sha256',json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
    }

    private function canonicalize(mixed $value): mixed
    {
        if(!is_array($value))return $value;
        if(array_is_list($value))return array_map(fn(mixed $item):mixed=>$this->canonicalize($item),$value);
        ksort($value);foreach($value as $key=>$item)$value[$key]=$this->canonicalize($item);return $value;
    }
}
