<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Publishing;

use App\Http\Controllers\Controller;
use App\Models\ContentSeries;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class SeriesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Publishing/Series',['series'=>ContentSeries::query()->withCount('documents')->orderBy('name')->get()->map(fn($s)=>['id'=>$s->id,'name'=>$s->name,'slug'=>$s->slug,'description'=>$s->description,'status'=>$s->status,'documents_count'=>$s->documents_count])->values()]);
    }
    public function store(Request $request, AuditManager $audit): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:200'],'slug'=>['nullable','string','max:180'],'description'=>['nullable','string','max:3000']]);
        $slug=Str::slug($data['slug'] ?: $data['name']); if(ContentSeries::query()->where('slug',$slug)->exists()) return back()->withErrors(['name'=>'A series with this slug already exists.']);
        $series=ContentSeries::query()->create(['uuid'=>(string)Str::uuid(),'name'=>trim($data['name']),'slug'=>$slug,'description'=>$data['description'] ?: null,'status'=>'active','metadata'=>[]]);
        $audit->record('publishing.series.created',$series,['name'=>$series->name]); return back()->with('success','Series created.');
    }

    public function update(Request $request, ContentSeries $series, AuditManager $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', 'string', 'in:active,archived'],
        ]);
        $slug = Str::slug($data['slug'] ?: $data['name']);
        if (ContentSeries::query()->where('slug', $slug)->whereKeyNot($series->id)->exists()) {
            return back()->withErrors(['name' => 'A series with this slug already exists.']);
        }
        $series->update(['name' => trim($data['name']), 'slug' => $slug, 'description' => $data['description'] ?: null, 'status' => $data['status']]);
        $audit->record('publishing.series.updated', $series, ['name' => $series->name, 'status' => $series->status]);
        return back()->with('success', 'Series updated.');
    }

    public function destroy(ContentSeries $series, AuditManager $audit): RedirectResponse
    {
        $audit->record('publishing.series.deleted',$series,['name'=>$series->name]); $series->delete(); return back()->with('success','Series deleted.');
    }
}
