<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Publishing;

use App\Http\Controllers\Controller;
use App\Models\TaxonomyTerm;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class TaxonomyController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Publishing/Taxonomy', [
            'terms' => TaxonomyTerm::query()->withCount('documents')->orderBy('taxonomy')->orderBy('name')->get()->map(fn ($term) => [
                'id'=>$term->id,'taxonomy'=>$term->taxonomy,'name'=>$term->name,'slug'=>$term->slug,'description'=>$term->description,'documents_count'=>$term->documents_count,
            ])->values(),
        ]);
    }
    public function store(Request $request, AuditManager $audit): RedirectResponse
    {
        $data=$request->validate(['taxonomy'=>['required',Rule::in(['category','topic','tag'])],'name'=>['required','string','max:180'],'slug'=>['nullable','string','max:180'],'description'=>['nullable','string','max:2000']]);
        $slug=Str::slug($data['slug'] ?: $data['name']);
        if (TaxonomyTerm::query()->where('taxonomy',$data['taxonomy'])->where('slug',$slug)->exists()) return back()->withErrors(['name'=>'A term with this name/slug already exists in this taxonomy.']);
        $term=TaxonomyTerm::query()->create(['uuid'=>(string)Str::uuid(),'taxonomy'=>$data['taxonomy'],'name'=>trim($data['name']),'slug'=>$slug,'description'=>$data['description'] ?: null]);
        $audit->record('publishing.taxonomy.created',$term,['taxonomy'=>$term->taxonomy]);
        return back()->with('success','Taxonomy term created.');
    }

    public function update(Request $request, TaxonomyTerm $term, AuditManager $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        $slug = Str::slug($data['slug'] ?: $data['name']);
        if (TaxonomyTerm::query()->where('taxonomy', $term->taxonomy)->where('slug', $slug)->whereKeyNot($term->id)->exists()) {
            return back()->withErrors(['name' => 'A term with this slug already exists in this taxonomy.']);
        }
        $term->update(['name' => trim($data['name']), 'slug' => $slug, 'description' => $data['description'] ?: null]);
        $audit->record('publishing.taxonomy.updated', $term, ['taxonomy' => $term->taxonomy]);
        return back()->with('success', 'Taxonomy term updated.');
    }

    public function destroy(TaxonomyTerm $term, AuditManager $audit): RedirectResponse
    {
        $audit->record('publishing.taxonomy.deleted',$term,['taxonomy'=>$term->taxonomy,'name'=>$term->name]);
        $term->delete(); return back()->with('success','Taxonomy term deleted.');
    }
}
