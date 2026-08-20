<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Commerce;

use App\Http\Controllers\Controller;
use App\Models\CommerceCurrency;
use App\Models\CommercePaymentProviderConfig;
use App\Models\CommerceTaxRate;
use App\Nexora\Commerce\Services\CurrencyManager;
use App\Nexora\Commerce\Services\PaymentProviderRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class CommerceSettingsController extends Controller
{
    public function index(Request $request, PaymentProviderRegistry $providers): Response
    {
        $registered=collect($providers->all())->map(fn($provider)=>['key'=>$provider->key(),'label'=>$provider->label(),'capabilities'=>$provider->capabilities()])->values();
        return Inertia::render('Admin/Commerce/Settings',[
            'currencies'=>CommerceCurrency::query()->orderByDesc('is_default')->orderBy('code')->get(),
            'taxRates'=>CommerceTaxRate::query()->latest()->get(),
            'registeredProviders'=>$registered,
            'providerConfigs'=>CommercePaymentProviderConfig::query()->orderBy('display_name')->get(),
        ]);
    }

    public function currency(Request $request, CurrencyManager $currencies): RedirectResponse
    {
        $data=$request->validate(['code'=>['required','string','size:3'],'name'=>['required','string','max:120'],'symbol'=>['nullable','string','max:12'],'minor_unit'=>['required','integer','min:0','max:4'],'enabled'=>['boolean'],'is_default'=>['boolean']]);
        $currencies->save($data['code'],$data['name'],$data['symbol']??null,(int)$data['minor_unit'],(bool)($data['enabled']??false),(bool)($data['is_default']??false));
        return back()->with('success','Currency settings saved.');
    }

    public function tax(Request $request): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:160'],'country_code'=>['nullable','string','size:2'],'region_code'=>['nullable','string','max:80'],'tax_code'=>['nullable','string','max:80'],'rate_percent'=>['required','numeric','min:0','max:100'],'inclusive'=>['boolean'],'active'=>['boolean']]);
        CommerceTaxRate::query()->create([
            'name'=>$data['name'],'country_code'=>isset($data['country_code'])?strtoupper($data['country_code']):null,'region_code'=>$data['region_code']??null,'tax_code'=>$data['tax_code']??null,
            'rate_basis_points'=>(int)round(((float)$data['rate_percent'])*100),'inclusive'=>(bool)($data['inclusive']??false),'active'=>(bool)($data['active']??true),
        ]);
        return back()->with('success','Tax rule created.');
    }

    public function provider(Request $request, PaymentProviderRegistry $providers): RedirectResponse
    {
        $data=$request->validate(['provider_key'=>['required','string','max:160'],'enabled'=>['boolean'],'mode'=>['required',Rule::in(['live','test'])]]);
        $provider=$providers->get($data['provider_key']);
        if (! $provider) return back()->with('error','This payment provider is not registered by an enabled extension.');
        $enable=(bool)($data['enabled']??false);
        $health=$enable ? $provider->health([]) : ['ok'=>true,'message'=>'Provider preference saved without enabling it.'];
        if ($enable && ! $health['ok']) return back()->with('error','Provider cannot be enabled: '.$health['message']);
        CommercePaymentProviderConfig::query()->updateOrCreate(['provider_key'=>$provider->key()],[
            'display_name'=>$provider->label(),'enabled'=>$enable,'mode'=>$data['mode'],'configuration'=>[],'secret_refs'=>[],
            'last_health_checked_at'=>$enable?now():null,'last_health_status'=>$enable?'healthy':null,'last_health_message'=>$enable?$health['message']:null,
        ]);
        return back()->with('success','Payment provider preference saved. Provider secrets remain extension-managed.');
    }

    public function health(CommercePaymentProviderConfig $config, PaymentProviderRegistry $providers): RedirectResponse
    {
        $provider=$providers->get($config->provider_key);
        if (! $provider) return back()->with('error','Provider extension is not currently registered.');
        $health=$provider->health((array)$config->configuration);
        $config->update(['last_health_checked_at'=>now(),'last_health_status'=>$health['ok']?'healthy':'unhealthy','last_health_message'=>$health['message']]);
        return back()->with($health['ok']?'success':'error',$health['message']);
    }
}
