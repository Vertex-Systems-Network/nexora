<?php

declare(strict_types=1);

namespace App\Nexora\Installation\Database;

use Illuminate\Database\DatabaseManager;
use PDO;
use Throwable;

final class DatabaseRuntimeDoctor
{
    public function __construct(private DatabaseManager $db,private DatabaseVersionPolicy $versions){}

    /** @return array{status:string,connection:string,reported_version:?string,normalized_version:?string,minimum:?string,error:?string} */
    public function inspect(): array
    {
        $connection=(string)config('database.default');
        $driver=$connection;
        try{
            $pdo=$this->db->connection($connection)->getPdo();
            $reported=$this->reportedVersion($pdo,$driver);
            $normalized=$this->versions->normalizedVersion($driver,$reported);
            $minimum=$this->versions->minimum($driver);
            $this->versions->assertSupported($driver,$reported);
            return ['status'=>'pass','connection'=>$connection,'reported_version'=>$reported,'normalized_version'=>$normalized,'minimum'=>$minimum,'error'=>null];
        }catch(Throwable $e){
            return ['status'=>'fail','connection'=>$connection,'reported_version'=>null,'normalized_version'=>null,'minimum'=>$this->safeMinimum($driver),'error'=>$e->getMessage()];
        }
    }

    private function reportedVersion(PDO $pdo,string $driver): string
    {
        return match($driver){
            'mysql','mariadb'=>(string)$pdo->query('SELECT VERSION()')->fetchColumn(),
            'pgsql'=>(string)$pdo->query('SHOW server_version')->fetchColumn(),
            'sqlite'=>(string)$pdo->query('SELECT sqlite_version()')->fetchColumn(),
            'sqlsrv'=>trim((string)$pdo->query("SELECT CAST(SERVERPROPERTY('ProductVersion') AS varchar(128))")->fetchColumn()),
            default=>throw new \RuntimeException('Unsupported primary database driver ['.$driver.'] for version certification.'),
        };
    }

    private function safeMinimum(string $driver): ?string
    {
        try{return $this->versions->minimum($driver);}catch(Throwable){return null;}
    }
}
