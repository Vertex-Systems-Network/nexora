<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Network;

use RuntimeException;

final class NetworkDestinationPolicy
{
    /** @return array{url:string,scheme:string,host:string,port:int,resolved_ips:list<string>,curl_resolve:list<string>} */
    public function external(string $url,bool $resolveDns=true): array
    {
        $parts=$this->parse($url);$scheme=$parts['scheme'];$host=$parts['host'];$port=$parts['port'];
        if((bool)config('nexora-network-runtime.external.require_https',true)&&!app()->environment(['local','testing'])&&$scheme!=='https')throw new RuntimeException('External Nexora network destinations require HTTPS.');
        $allowed=array_map('intval',(array)config('nexora-network-runtime.external.allowed_ports',[443]));
        if($allowed!==[]&&!in_array($port,$allowed,true))throw new RuntimeException('External network destination port is not allowed by Nexora policy.');
        foreach((array)config('nexora-network-runtime.external.blocked_host_suffixes',[]) as $suffix){$suffix=strtolower(trim((string)$suffix));if($suffix!==''&&($host===ltrim($suffix,'.')||str_ends_with($host,$suffix)))throw new RuntimeException('External network destination host is blocked by Nexora policy.');}
        $ips=$resolveDns||filter_var($host,FILTER_VALIDATE_IP)?$this->resolve($host):[];
        if((bool)config('nexora-network-runtime.external.require_dns_resolution',true)&&$resolveDns&&$ips===[])throw new RuntimeException('External network destination DNS could not be resolved.');
        if((bool)config('nexora-network-runtime.external.block_private_reserved',true))foreach($ips as $ip)$this->assertPublicIp($ip);
        return ['url'=>$url,'scheme'=>$scheme,'host'=>$host,'port'=>$port,'resolved_ips'=>$ips,'curl_resolve'=>$this->curlResolve($host,$port,$ips)];
    }

    /** @return array{url:string,scheme:string,host:string,port:int,resolved_ips:list<string>,curl_resolve:list<string>} */
    public function sameOrigin(string $url,string $origin): array
    {
        $target=$this->parse($url);$base=$this->parse($origin);
        if($target['scheme']!==$base['scheme']||$target['host']!==$base['host']||$target['port']!==$base['port'])throw new RuntimeException('Nexora same-origin network request attempted to leave the configured origin.');
        $ips=$this->resolve($target['host']);if($ips===[])throw new RuntimeException('Same-origin network destination DNS could not be resolved.');
        return ['url'=>$url,'scheme'=>$target['scheme'],'host'=>$target['host'],'port'=>$target['port'],'resolved_ips'=>$ips,'curl_resolve'=>$this->curlResolve($target['host'],$target['port'],$ips)];
    }

    /** @return array{scheme:string,host:string,port:int} */
    private function parse(string $url): array
    {
        $parts=parse_url(trim($url));if(!is_array($parts))throw new RuntimeException('Network destination URL is invalid.');
        if(isset($parts['user'])||isset($parts['pass']))throw new RuntimeException('Network destination URLs must not contain embedded credentials.');
        $scheme=strtolower((string)($parts['scheme']??''));$host=strtolower(trim((string)($parts['host']??'')));
        if(!in_array($scheme,['http','https'],true)||$host==='')throw new RuntimeException('Network destination must be an absolute HTTP(S) URL.');
        $port=(int)($parts['port']??($scheme==='https'?443:80));if($port<1||$port>65535)throw new RuntimeException('Network destination port is invalid.');
        return ['scheme'=>$scheme,'host'=>$host,'port'=>$port];
    }

    /** @return list<string> */
    private function resolve(string $host): array
    {
        if(filter_var($host,FILTER_VALIDATE_IP))return [$host];
        $records=@dns_get_record($host,DNS_A|DNS_AAAA)?:[];$ips=[];
        foreach($records as $record){if(isset($record['ip'])&&filter_var($record['ip'],FILTER_VALIDATE_IP))$ips[]=(string)$record['ip'];if(isset($record['ipv6'])&&filter_var($record['ipv6'],FILTER_VALIDATE_IP))$ips[]=(string)$record['ipv6'];}
        $ips=array_values(array_unique($ips));sort($ips,SORT_STRING);return $ips;
    }

    private function assertPublicIp(string $ip): void
    {
        if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)===false)throw new RuntimeException('Private or reserved external network destination addresses are blocked.');
    }

    /** @param list<string> $ips @return list<string> */
    private function curlResolve(string $host,int $port,array $ips): array
    {
        if(!(bool)config('nexora-network-runtime.external.require_dns_pin',true)||$ips===[])return [];
        if(!defined('CURLOPT_RESOLVE'))throw new RuntimeException('Nexora DNS pinning requires the PHP cURL extension with CURLOPT_RESOLVE support.');
        $ipv4=array_values(array_filter($ips,static fn(string $ip):bool=>filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)!==false));
        $selected=$ipv4[0]??$ips[0];
        if(filter_var($selected,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6)!==false)$selected='['.$selected.']';
        return [$host.':'.$port.':'.$selected];
    }
}
