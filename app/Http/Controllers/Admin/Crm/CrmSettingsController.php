<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmCustomFieldDefinition;
use App\Models\CrmPipeline;
use App\Models\CrmPipelineStage;
use App\Nexora\Crm\Services\CrmActivityProviderRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class CrmSettingsController extends Controller
{
    public function index(CrmActivityProviderRegistry $providers): Response
    {
        return Inertia::render('Admin/Crm/Settings',[
            'pipelines'=>CrmPipeline::query()->with('stages')->orderByDesc('is_default')->orderBy('name')->get()->map(fn($p)=>['id'=>$p->id,'name'=>$p->name,'slug'=>$p->slug,'is_default'=>$p->is_default,'active'=>$p->active,'stages'=>$p->stages->map(fn($s)=>['id'=>$s->id,'name'=>$s->name,'slug'=>$s->slug,'position'=>$s->position,'probability'=>$s->probability,'is_won'=>$s->is_won,'is_lost'=>$s->is_lost])->values()]),
            'customFields'=>CrmCustomFieldDefinition::query()->orderBy('entity_type')->orderBy('position')->get()->map(fn($f)=>['id'=>$f->id,'entity_type'=>$f->entity_type,'key'=>$f->key,'label'=>$f->label,'field_type'=>$f->field_type,'options'=>$f->options??[],'required'=>$f->required,'active'=>$f->active,'position'=>$f->position]),
            'activityProviders'=>collect($providers->all())->map(fn($provider,$key)=>['key'=>$key,'label'=>$provider->label(),'capabilities'=>$provider->capabilities()])->values(),
        ]);
    }

    public function pipeline(Request $request): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:160'],'slug'=>['nullable','string','max:180','unique:nx_crm_pipelines,slug'],'is_default'=>['nullable','boolean']]);
        $slug=$data['slug']?:Str::slug($data['name']);
        if ($data['is_default']??false) CrmPipeline::query()->update(['is_default'=>false]);
        CrmPipeline::query()->create(['name'=>$data['name'],'slug'=>$slug,'is_default'=>(bool)($data['is_default']??false),'active'=>true]);
        return back()->with('success','Pipeline created.');
    }

    public function stage(Request $request, CrmPipeline $pipeline): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:160'],'slug'=>['nullable','string','max:180'],'position'=>['required','integer','min:0','max:10000'],'probability'=>['required','integer','min:0','max:100'],'outcome'=>['required','in:open,won,lost']]);
        $slug=$data['slug']?:Str::slug($data['name']);
        if (CrmPipelineStage::query()->where('pipeline_id',$pipeline->id)->where('slug',$slug)->exists()) return back()->withErrors(['slug'=>'That stage key already exists in this pipeline.']);
        CrmPipelineStage::query()->create(['pipeline_id'=>$pipeline->id,'name'=>$data['name'],'slug'=>$slug,'position'=>$data['position'],'probability'=>$data['probability'],'is_won'=>$data['outcome']==='won','is_lost'=>$data['outcome']==='lost']);
        return back()->with('success','Pipeline stage created.');
    }

    public function customField(Request $request): RedirectResponse
    {
        $data=$request->validate([
            'entity_type'=>['required','in:organization,contact,lead,opportunity'],'key'=>['required','regex:/^[a-z][a-z0-9_]{1,119}$/'],'label'=>['required','string','max:180'],
            'field_type'=>['required','in:text,number,date,datetime,select,multi_select,checkbox'],'options'=>['nullable','string','max:5000'],'required'=>['nullable','boolean'],'position'=>['required','integer','min:0','max:10000'],
        ]);
        $options=[]; if (in_array($data['field_type'],['select','multi_select'],true)) $options=array_values(array_filter(array_map('trim',preg_split('/[\r\n,]+/',(string)($data['options']??''))?:[])));
        CrmCustomFieldDefinition::query()->create(['entity_type'=>$data['entity_type'],'key'=>$data['key'],'label'=>$data['label'],'field_type'=>$data['field_type'],'options'=>$options,'required'=>(bool)($data['required']??false),'active'=>true,'position'=>$data['position']]);
        return back()->with('success','Custom field created.');
    }
}
