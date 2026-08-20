<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Network;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class ApprovedHttpClient
{
    public function __construct(private readonly NetworkDestinationPolicy $policy) {}

    public function external(string $url): PendingRequest
    {
        return $this->pending($this->policy->external($url,true));
    }

    public function sameOrigin(string $url,string $origin): PendingRequest
    {
        return $this->pending($this->policy->sameOrigin($url,$origin));
    }

    /** @param array{curl_resolve:list<string>} $inspection */
    private function pending(array $inspection): PendingRequest
    {
        $options=['allow_redirects'=>false];
        if((bool)config('nexora-network-runtime.tls.verify_peer',true)){
            $ca=trim((string)config('nexora-network-runtime.tls.ca_bundle',''));
            $options['verify']=$ca!==''?$ca:true;
        }else{$options['verify']=false;}
        if(($inspection['curl_resolve']??[])!==[])$options['curl']=[CURLOPT_RESOLVE=>$inspection['curl_resolve']];
        return Http::withoutRedirecting()->withOptions($options);
    }
}
