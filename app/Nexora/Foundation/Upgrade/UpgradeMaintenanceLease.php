<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Upgrade;

use App\Nexora\Foundation\Filesystem\AtomicFileWriter;

final class UpgradeMaintenanceLease
{
    public function __construct(private readonly AtomicFileWriter $files) {}

    public function path(): string
    {
        return (string) config('nexora-upgrade.maintenance_lease_path', base_path('storage/app/nexora/upgrade/maintenance-lease.json'));
    }

    /** @return array<string,mixed> */
    public function acquire(string $upgradeId): array
    {
        if ((bool) config('nexora-upgrade.block_preexisting_maintenance', true) && app()->isDownForMaintenance()) {
            throw new \RuntimeException('Nexora was already in maintenance mode before this upgrade. Upgrade apply refuses to take ownership of an existing maintenance state.');
        }
        if (is_file($this->path())) {
            $existing=$this->read();
            throw new \RuntimeException('An upgrade maintenance lease already exists for ['.(string)($existing['upgrade_id']??'unknown').']. Resolve it before starting another upgrade.');
        }
        $payload=[
            'schema'=>1,
            'status'=>'owned',
            'upgrade_id'=>$upgradeId,
            'acquired_at'=>now()->toIso8601String(),
            'maintenance_was_active_before_upgrade'=>false,
        ];
        return $this->publish($payload);
    }

    /** @return array<string,mixed>|null */
    public function read(): ?array
    {
        $path=$this->path(); if(!is_file($path)) return null;
        $payload=json_decode((string)file_get_contents($path),true);
        if(!is_array($payload)) throw new \RuntimeException('Upgrade maintenance lease is unreadable.');
        $expected=trim((string)($payload['lease_sha256']??''));
        $actual=hash('sha256',json_encode($this->canonical($payload),JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        if($expected===''||!hash_equals($expected,$actual)) throw new \RuntimeException('Upgrade maintenance lease integrity verification failed.');
        return $payload;
    }

    /** @return array<string,mixed> */
    public function verify(string $upgradeId): array
    {
        $lease=$this->read();
        if(!is_array($lease)||($lease['status']??null)!=='owned'||($lease['upgrade_id']??null)!==$upgradeId) {
            throw new \RuntimeException('Upgrade maintenance lease no longer belongs to the active upgrade transaction.');
        }
        return $lease;
    }

    public function release(string $upgradeId): void
    {
        $this->verify($upgradeId);
        if(is_file($this->path())&&!@unlink($this->path())) throw new \RuntimeException('Unable to release the upgrade maintenance lease.');
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function publish(array $payload): array
    {
        $payload['lease_sha256']=hash('sha256',json_encode($this->canonical($payload),JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        $this->files->write($this->path(),json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",0755,0600);
        return $payload;
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function canonical(array $payload): array { unset($payload['lease_sha256']); ksort($payload); return $payload; }
}
