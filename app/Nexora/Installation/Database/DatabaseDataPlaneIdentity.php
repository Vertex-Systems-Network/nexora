<?php

declare(strict_types=1);

namespace App\Nexora\Installation\Database;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;
use PDO;

final class DatabaseDataPlaneIdentity
{
    public function __construct(private DatabaseManager $db,private DatabaseVersionPolicy $versions) {}

    /** @return array<string,mixed> */
    public function current(bool $deep=false): array
    {
        $connection=(string)config('database.default');
        $configured=(array)config("database.connections.{$connection}",[]);
        $driver=$this->logicalDriver($connection,(string)($configured['driver']??$connection));
        $pdo=$this->db->connection($connection)->getPdo();
        $session=$this->sessionProfile($pdo,$driver);
        $reported=(string)($session['server_version']??'');
        $normalized=$this->versions->normalizedVersion($driver,$reported);
        $this->versions->assertSupported($driver,$reported);
        $databaseName=(string)($configured['database']??'');
        if($driver==='sqlite')$databaseName=basename(str_replace('\\','/',$databaseName));
        $materials=[
            'schema'=>1,
            'connection'=>$connection,
            'driver'=>$driver,
            'database_name_sha256'=>hash('sha256',$databaseName),
            'server_version'=>(bool)config('nexora-database-runtime.require_exact_server_version',true)?$normalized:$this->majorMinor($normalized),
            'session_profile'=>(bool)config('nexora-database-runtime.require_exact_session_profile',true)?$session:[],
        ];
        $fingerprint=$this->hash($materials);
        $result=['schema'=>1,'status'=>'pass','connection'=>$connection,'driver'=>$driver,'reported_server_version'=>$reported,'normalized_server_version'=>$normalized,'database_name_sha256'=>$materials['database_name_sha256'],'session_profile'=>$session,'fingerprint'=>$fingerprint];
        if($deep){$schema=$this->schemaSnapshot();$result['schema_snapshot']=$schema;$result['schema_fingerprint']=$schema['schema_fingerprint'];}
        return $result;
    }

    public function fingerprintValue(): string { return (string)$this->current(false)['fingerprint']; }

    /** @return array<string,mixed> */
    public function schemaSnapshot(): array
    {
        $tables=[];
        foreach((array)Schema::getTables() as $table){
            $name=(string)($table['name']??$table['table']??'');if($name==='')continue;
            $columns=[];foreach((array)Schema::getColumns($name) as $column){$columns[]=$this->select($column,['name','type','type_name','nullable','default','auto_increment','collation','comment']);}
            $indexes=[];foreach((array)Schema::getIndexes($name) as $index){$indexes[]=$this->select($index,['name','columns','type','unique','primary']);}
            $foreign=[];foreach((array)Schema::getForeignKeys($name) as $fk){$foreign[]=$this->select($fk,['name','columns','foreign_schema','foreign_table','foreign_columns','on_update','on_delete']);}
            usort($columns,$this->sorter('name'));usort($indexes,$this->sorter('name'));usort($foreign,$this->sorter('name'));
            $tables[]=['name'=>$name,'columns'=>$columns,'indexes'=>$indexes,'foreign_keys'=>$foreign];
        }
        usort($tables,$this->sorter('name'));
        $views=[];
        if((bool)config('nexora-database-runtime.schema_include_views',true) && method_exists(Schema::getFacadeRoot(),'getViews')){
            foreach((array)Schema::getViews() as $view){$name=(string)($view['name']??'');if($name==='')continue;$definition=(string)($view['definition']??'');$views[]=['name'=>$name,'definition_sha256'=>hash('sha256',preg_replace('/\s+/',' ',trim($definition))??trim($definition))];}
            usort($views,$this->sorter('name'));
        }
        $payload=['schema'=>1,'driver'=>$this->logicalDriver((string)config('database.default'),(string)$this->db->connection()->getDriverName()),'tables'=>$tables,'views'=>$views];
        $payload['schema_fingerprint']=$this->hash($payload);
        $payload['table_count']=count($tables);$payload['view_count']=count($views);
        return $payload;
    }

    /** @return array<string,mixed> */
    private function sessionProfile(PDO $pdo,string $driver): array
    {
        return match($driver){
            'mysql','mariadb'=>$this->mysqlProfile($pdo),
            'pgsql'=>$this->pgsqlProfile($pdo),
            'sqlite'=>$this->sqliteProfile($pdo),
            'sqlsrv'=>$this->sqlsrvProfile($pdo),
            default=>throw new \RuntimeException('Unsupported database driver ['.$driver.'] for data-plane identity.'),
        };
    }

    /** @return array<string,mixed> */
    private function mysqlProfile(PDO $pdo): array
    {
        try{$row=$pdo->query("SELECT VERSION() AS server_version, @@character_set_connection AS charset, @@collation_connection AS collation, @@session.time_zone AS time_zone, @@session.sql_mode AS sql_mode, @@transaction_isolation AS isolation_level")->fetch(PDO::FETCH_ASSOC);}
        catch(\Throwable){$row=$pdo->query("SELECT VERSION() AS server_version, @@character_set_connection AS charset, @@collation_connection AS collation, @@session.time_zone AS time_zone, @@session.sql_mode AS sql_mode, @@tx_isolation AS isolation_level")->fetch(PDO::FETCH_ASSOC);}
        return $this->normalizedMap(is_array($row)?$row:[]);
    }

    /** @return array<string,mixed> */
    private function pgsqlProfile(PDO $pdo): array
    {
        $row=$pdo->query("SELECT current_setting('server_version') AS server_version, current_setting('client_encoding') AS charset, current_setting('TimeZone') AS time_zone, current_setting('transaction_isolation') AS isolation_level, current_setting('search_path') AS search_path, current_setting('standard_conforming_strings') AS standard_conforming_strings")->fetch(PDO::FETCH_ASSOC);
        $collation=$pdo->query("SELECT datcollate FROM pg_database WHERE datname=current_database()")->fetchColumn();$row=is_array($row)?$row:[];$row['collation']=$collation;
        return $this->normalizedMap($row);
    }

    /** @return array<string,mixed> */
    private function sqliteProfile(PDO $pdo): array
    {
        $one=static fn(string $pragma):string=>(string)$pdo->query($pragma)->fetchColumn();
        return $this->normalizedMap(['server_version'=>(string)$pdo->query('SELECT sqlite_version()')->fetchColumn(),'encoding'=>$one('PRAGMA encoding'),'foreign_keys'=>$one('PRAGMA foreign_keys'),'journal_mode'=>$one('PRAGMA journal_mode'),'synchronous'=>$one('PRAGMA synchronous'),'busy_timeout'=>$one('PRAGMA busy_timeout')]);
    }

    /** @return array<string,mixed> */
    private function sqlsrvProfile(PDO $pdo): array
    {
        $row=$pdo->query("SELECT CAST(SERVERPROPERTY('ProductVersion') AS varchar(128)) AS server_version, CAST(DATABASEPROPERTYEX(DB_NAME(),'Collation') AS varchar(256)) AS collation, SESSIONPROPERTY('ANSI_NULLS') AS ansi_nulls, SESSIONPROPERTY('ANSI_WARNINGS') AS ansi_warnings, SESSIONPROPERTY('QUOTED_IDENTIFIER') AS quoted_identifier")->fetch(PDO::FETCH_ASSOC);$options=[];
        try{$stmt=$pdo->query('DBCC USEROPTIONS');while($r=$stmt->fetch(PDO::FETCH_NUM)){if(isset($r[0],$r[1]))$options[strtolower((string)$r[0])]=(string)$r[1];}}catch(\Throwable){}
        $row=is_array($row)?$row:[];$row['isolation_level']=$options['isolation level']??null;$row['language']=$options['language']??null;$row['dateformat']=$options['dateformat']??null;
        return $this->normalizedMap($row);
    }

    private function logicalDriver(string $connection,string $driver): string
    {
        $hay=strtolower($connection.' '.$driver);if(str_contains($hay,'mariadb'))return 'mariadb';if(str_contains($hay,'mysql'))return 'mysql';if(str_contains($hay,'pgsql')||str_contains($hay,'postgres'))return 'pgsql';if(str_contains($hay,'sqlite'))return 'sqlite';if(str_contains($hay,'sqlsrv'))return 'sqlsrv';return strtolower($driver);
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function normalizedMap(array $row): array {ksort($row);foreach($row as $k=>$v){$row[$k]=$v===null?null:trim((string)$v);}return $row;}
    private function majorMinor(string $version): string {$p=explode('.',$version);return implode('.',array_slice($p,0,2));}
    /** @param array<string,mixed> $payload */
    private function hash(array $payload): string {unset($payload['schema_fingerprint']);return hash('sha256',json_encode($this->canonical($payload),JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));}
    private function canonical(mixed $v): mixed {if(!is_array($v))return $v;if(array_is_list($v))return array_map(fn($i)=>$this->canonical($i),$v);ksort($v);foreach($v as $k=>$i)$v[$k]=$this->canonical($i);return $v;}
    /** @param array<string,mixed> $row @param list<string> $keys @return array<string,mixed> */
    private function select(array $row,array $keys): array {$out=[];foreach($keys as $key)if(array_key_exists($key,$row))$out[$key]=$this->canonical($row[$key]);return $out;}
    private function sorter(string $key): callable {return static fn(array $a,array $b):int=>strcmp((string)($a[$key]??''),(string)($b[$key]??''));}
}
