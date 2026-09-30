<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

use App\Models\RuntimeLease;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class RuntimeLeaseManager
{
    public function __construct(private readonly RuntimeHostClockIdentity $clock) {}

    public function acquireOrRenew(string $name, string $owner, int $ttlSeconds = 90, array $metadata = []): bool
    {
        // Distributed leadership must never degrade to implicit success. If the
        // lease table is unavailable, callers must fail closed rather than let
        // multiple nodes believe they own the same activity.
        if (! Schema::hasTable('nx_runtime_leases')) return false;
        $ttlSeconds = max(15, min(3600, $ttlSeconds));

        return DB::transaction(function () use ($name, $owner, $ttlSeconds, $metadata): bool {
            DB::table('nx_runtime_leases')->insertOrIgnore([
                'id' => (string) Str::uuid(),
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            /** @var RuntimeLease|null $lease */
            $lease = RuntimeLease::query()->where('name', $name)->lockForUpdate()->first();
            if ($lease === null) return false;

            $now = $this->clock->databaseNow();
            $expired = $lease->expires_at === null || $lease->expires_at->lte($now);
            $sameOwner = $lease->owner_node_key === $owner;
            if (! $expired && ! $sameOwner) return false;

            $lease->forceFill([
                'owner_node_key' => $owner,
                'token' => hash('sha256', $name.'|'.$owner.'|'.Str::random(32)),
                'expires_at' => $now->copy()->addSeconds($ttlSeconds),
                'heartbeat_at' => $now,
                'metadata' => $metadata,
            ])->save();

            return true;
        }, 3);
    }

    /** @param array<string,mixed> $metadata */
    public function acquireActivityUnlessBarrierActive(string $name,string $owner,int $ttlSeconds,array $metadata,string $barrierName): bool
    {
        // Barrier-aware work has the same fail-closed requirement as leadership:
        // no coordination table means there is no trustworthy proof that the
        // barrier is inactive or that this node owns the activity.
        if (! Schema::hasTable('nx_runtime_leases')) return false;
        $ttlSeconds=max(15,min(7200,$ttlSeconds));
        return DB::transaction(function () use ($name,$owner,$ttlSeconds,$metadata,$barrierName): bool {
            $now=$this->clock->databaseNow();
            DB::table('nx_runtime_leases')->insertOrIgnore(['id'=>(string)Str::uuid(),'name'=>$barrierName,'created_at'=>$now,'updated_at'=>$now]);
            /** @var RuntimeLease|null $barrier */
            $barrier=RuntimeLease::query()->where('name',$barrierName)->lockForUpdate()->first();
            if($barrier===null)return false;
            $barrierMeta=is_array($barrier->metadata)?$barrier->metadata:[];
            $recoveryRequired=($barrierMeta['recovery_required']??false)===true;
            $barrierActive=$recoveryRequired||($barrier->owner_node_key!==null&&$barrier->expires_at!==null&&$barrier->expires_at->gt($now));
            if($barrierActive)return false;

            DB::table('nx_runtime_leases')->insertOrIgnore(['id'=>(string)Str::uuid(),'name'=>$name,'created_at'=>$now,'updated_at'=>$now]);
            /** @var RuntimeLease|null $lease */
            $lease=RuntimeLease::query()->where('name',$name)->lockForUpdate()->first();
            if($lease===null)return false;
            $expired=$lease->expires_at===null||$lease->expires_at->lte($now);
            $sameOwner=$lease->owner_node_key===$owner;
            if(!$expired&&!$sameOwner)return false;
            $lease->forceFill([
                'owner_node_key'=>$owner,
                'token'=>hash('sha256',$name.'|'.$owner.'|'.Str::random(32)),
                'expires_at'=>$now->copy()->addSeconds($ttlSeconds),
                'heartbeat_at'=>$now,
                'metadata'=>$metadata,
            ])->save();
            return true;
        },3);
    }

    /** @return array{active:bool,owner_node_key:?string,expires_at:?string,recovery_required:bool} */
    public function barrierStatus(string $barrierName): array
    {
        if(!Schema::hasTable('nx_runtime_leases'))return ['active'=>false,'owner_node_key'=>null,'expires_at'=>null,'recovery_required'=>false];
        $lease=RuntimeLease::query()->where('name',$barrierName)->first();$now=$this->clock->databaseNow();$meta=is_array($lease?->metadata)?$lease->metadata:[];$recoveryRequired=($meta['recovery_required']??false)===true;
        $active=$recoveryRequired||($lease!==null&&$lease->owner_node_key!==null&&$lease->expires_at!==null&&$lease->expires_at->gt($now));
        return ['active'=>$active,'owner_node_key'=>$lease?->owner_node_key,'expires_at'=>$lease?->expires_at?->toIso8601String(),'recovery_required'=>$recoveryRequired];
    }

    public function release(string $name, string $owner): void
    {
        if (! Schema::hasTable('nx_runtime_leases')) return;
        RuntimeLease::query()->where('name', $name)->where('owner_node_key', $owner)->update([
            'owner_node_key' => null,
            'token' => null,
            'expires_at' => $this->clock->databaseNow(),
            'updated_at' => $this->clock->databaseNow(),
        ]);
    }
}
