<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Settings;

use App\Models\EnterpriseSetting;
use App\Models\Setting;
use App\Nexora\Enterprise\Services\TenantContext;
use App\Nexora\Foundation\Contracts\SettingsContract;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class DatabaseSettingsRepository implements SettingsContract
{
    public function get(string $key, mixed $default = null): mixed
    {
        $tenantId=$this->tenantId();
        if($tenantId!==null){$tenant=EnterpriseSetting::query()->where('organization_id',$tenantId)->where('key',$key)->first();if($tenant!==null)return $tenant->value;}
        $setting = Setting::query()->where('key', $key)->first();
        return $setting === null ? $default : $this->decode($setting->value, $setting->type);
    }

    public function set(string $key, mixed $value): void
    {
        $tenantId=$this->tenantId();
        if($tenantId!==null){EnterpriseSetting::query()->updateOrCreate(['organization_id'=>$tenantId,'key'=>$key],['id'=>(string)Str::uuid(),'value'=>$value,'type'=>$this->type($value)]);return;}
        [$type, $encoded] = $this->encode($value);
        $group = str_contains($key, '.') ? explode('.', $key, 2)[0] : 'general';
        Setting::query()->updateOrCreate(['key' => $key],['group' => $group, 'value' => $encoded, 'type' => $type]);
    }

    private function tenantId(): ?string
    {
        if(!Schema::hasTable('nx_enterprise_settings')||!app()->bound(TenantContext::class))return null;
        return app(TenantContext::class)->id();
    }
    private function type(mixed $value): string{return match(true){is_bool($value)=>'boolean',is_int($value)=>'integer',is_float($value)=>'float',is_array($value),is_object($value)=>'json',$value===null=>'null',default=>'string'};}
    private function encode(mixed $value): array{return match(true){is_bool($value)=>['boolean',$value?'1':'0'],is_int($value)=>['integer',(string)$value],is_float($value)=>['float',(string)$value],is_array($value),is_object($value)=>['json',json_encode($value,JSON_THROW_ON_ERROR)],$value===null=>['null',null],default=>['string',(string)$value]};}
    private function decode(?string $value,string $type):mixed{return match($type){'boolean'=>$value==='1','integer'=>(int)$value,'float'=>(float)$value,'json'=>$value===null?null:json_decode($value,true,flags:JSON_THROW_ON_ERROR),'null'=>null,default=>$value};}
}
