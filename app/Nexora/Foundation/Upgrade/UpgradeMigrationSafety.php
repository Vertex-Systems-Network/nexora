<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Upgrade;

final class UpgradeMigrationSafety
{
    /** @param list<string> $pending @return array<string,mixed> */
    public function assess(array $pending): array
    {
        $findings=[];$files=[];
        foreach($pending as $migration){$migration=(string)$migration;$matches=glob(database_path('migrations/'.$migration.'.php'))?:[];if(count($matches)!==1){$findings[]=['migration'=>$migration,'rule'=>'missing-source','detail'=>'Pending migration source file could not be resolved uniquely.'];continue;}$path=$matches[0];$content=(string)file_get_contents($path);$body=$this->methodBody($content,'up');$sha=hash('sha256',$content);$files[]=['migration'=>$migration,'sha256'=>$sha];if($body===null){$findings[]=['migration'=>$migration,'rule'=>'missing-up-method','detail'=>'Unable to parse migration up() method.'];continue;}foreach($this->destructiveRules() as $rule=>$pattern){if(preg_match($pattern,$body)===1)$findings[]=['migration'=>$migration,'rule'=>$rule,'detail'=>'Destructive/contract-breaking operation detected in up() method.'];}}
        usort($files,static fn(array $a,array $b):int=>strcmp($a['migration'],$b['migration']));usort($findings,static fn(array $a,array $b):int=>[$a['migration'],$a['rule']]<=>[$b['migration'],$b['rule']]);
        $payload=['status'=>$findings===[]?'pass':'fail','pending_count'=>count($pending),'files'=>$files,'findings'=>$findings,'automatic_destructive_migration_approval'=>false];$payload['migration_safety_sha256']=$this->hash($payload);return $payload;
    }

    /** @return array<string,string> */
    private function destructiveRules(): array{return [
        'schema-drop'=>'/Schema\s*::\s*(?:drop|dropIfExists|rename)\s*\(/i',
        'column-drop'=>'/->\s*(?:dropColumn|dropConstrainedForeignId|dropForeign|dropIndex|dropUnique|dropPrimary|renameColumn)\s*\(/i',
        'column-change'=>'/->\s*change\s*\(/i',
        'raw-destructive-sql'=>'/\b(?:ALTER\s+TABLE\b[^;]*(?:\bDROP\b|\bRENAME\b)|TRUNCATE\s+TABLE|DROP\s+TABLE)\b/is',
    ];}

    private function methodBody(string $source,string $method): ?string
    {
        $tokens=token_get_all($source);$count=count($tokens);for($i=0;$i<$count;$i++){if(!is_array($tokens[$i])||$tokens[$i][0]!==T_FUNCTION)continue;$j=$i+1;while($j<$count&&((is_array($tokens[$j])&&in_array($tokens[$j][0],[T_WHITESPACE,T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG,T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG],true))||$tokens[$j]==='&'))$j++;if($j>=$count||!is_array($tokens[$j])||$tokens[$j][0]!==T_STRING||strcasecmp($tokens[$j][1],$method)!==0)continue;while($j<$count&&$tokens[$j]!=='{')$j++;if($j>=$count)return null;$depth=0;$body='';for(;$j<$count;$j++){$t=$tokens[$j];$text=is_array($t)?$t[1]:$t;if($text==='{'){$depth++;if($depth===1)continue;}if($text==='}'){$depth--;if($depth===0)return $body;}$body.=$text;}return null;}return null;
    }

    /** @param array<string,mixed> $payload */
    private function hash(array $payload): string{unset($payload['migration_safety_sha256']);$payload=$this->canonical($payload);return hash('sha256',json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));}
    private function canonical(mixed $v): mixed{if(!is_array($v))return $v;if(array_is_list($v))return array_map(fn($x)=>$this->canonical($x),$v);ksort($v);foreach($v as $k=>$x)$v[$k]=$this->canonical($x);return $v;}
}
