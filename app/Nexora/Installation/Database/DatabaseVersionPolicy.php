<?php

declare(strict_types=1);

namespace App\Nexora\Installation\Database;

use RuntimeException;

final class DatabaseVersionPolicy
{
    /** @var array<string,string> */
    private const MINIMUM = [
        'mysql' => '5.7.0',
        'mariadb' => '10.3.0',
        'pgsql' => '10.0.0',
        'sqlite' => '3.26.0',
        'sqlsrv' => '14.0.0', // SQL Server 2017
    ];

    public function minimum(string $driver): string
    {
        $driver=$this->normalizeDriver($driver);
        return self::MINIMUM[$driver] ?? throw new RuntimeException('No database minimum-version policy exists for ['.$driver.'].');
    }

    public function normalizedVersion(string $driver,string $reported): string
    {
        $driver=$this->normalizeDriver($driver);
        if($driver==='mariadb' && preg_match('/(\d+\.\d+(?:\.\d+)?)-MariaDB/i',$reported,$match)===1) return $this->threePart($match[1]);
        if(preg_match('/(\d+\.\d+(?:\.\d+)?)/',$reported,$match)!==1) throw new RuntimeException('Unable to parse database server version ['.$reported.'].');
        return $this->threePart($match[1]);
    }

    public function assertSupported(string $driver,string $reported): void
    {
        $normalized=$this->normalizedVersion($driver,$reported);
        $minimum=$this->minimum($driver);
        if(version_compare($normalized,$minimum,'<')){
            throw new RuntimeException(sprintf('%s %s is below Nexora minimum %s.', $this->label($driver), $normalized, $minimum));
        }
    }

    public function label(string $driver): string
    {
        return match($this->normalizeDriver($driver)){
            'mysql'=>'MySQL','mariadb'=>'MariaDB','pgsql'=>'PostgreSQL','sqlite'=>'SQLite','sqlsrv'=>'Microsoft SQL Server',default=>$driver,
        };
    }

    private function normalizeDriver(string $driver): string
    {
        $driver=strtolower(trim($driver));
        if(str_contains($driver,'mariadb')) return 'mariadb';
        if(str_contains($driver,'mysql')) return 'mysql';
        if(str_contains($driver,'pgsql')||str_contains($driver,'postgres')) return 'pgsql';
        if(str_contains($driver,'sqlite')) return 'sqlite';
        if(str_contains($driver,'sqlsrv')||str_contains($driver,'sqlserver')) return 'sqlsrv';
        return $driver;
    }

    private function threePart(string $version): string
    {
        $parts=explode('.',$version);
        return implode('.',array_pad(array_slice($parts,0,3),3,'0'));
    }
}
