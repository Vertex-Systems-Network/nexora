<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Publishing;

use App\Http\Controllers\Controller;
use App\Models\AuthorProfile;
use App\Models\User;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class AuthorProfileController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Publishing/Authors', [
            'authors'=>AuthorProfile::query()->with('user:id,name,email')->withCount('documents')->orderBy('display_name')->get()->map(fn($a)=>['id'=>$a->id,'display_name'=>$a->display_name,'slug'=>$a->slug,'bio'=>$a->bio,'avatar_url'=>$a->avatar_url,'website_url'=>$a->website_url,'is_public'=>$a->is_public,'user'=>$a->user?->only(['id','name','email']),'documents_count'=>$a->documents_count])->values(),
            'users'=>User::query()->where('status','active')->orderBy('name')->get(['id','name','email'])->values(),
        ]);
    }
    public function store(Request $request, AuditManager $audit): RedirectResponse
    {
        $data=$request->validate(['user_id'=>['nullable','integer','exists:users,id'],'display_name'=>['required','string','max:180'],'slug'=>['nullable','string','max:180'],'bio'=>['nullable','string','max:5000'],'avatar_url'=>['nullable','url:http,https','max:2048'],'website_url'=>['nullable','url:http,https','max:2048'],'is_public'=>['required','boolean']]);
        $slug=Str::slug($data['slug'] ?: $data['display_name']);
        if(AuthorProfile::query()->where('slug',$slug)->exists()) return back()->withErrors(['display_name'=>'An author profile with this slug already exists.']);
        $author=AuthorProfile::query()->create([...$data,'uuid'=>(string)Str::uuid(),'slug'=>$slug]);
        $audit->record('publishing.author.created',$author,['display_name'=>$author->display_name]);
        return back()->with('success','Author profile created.');
    }

    public function update(Request $request, AuthorProfile $author, AuditManager $audit): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'display_name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:180'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'avatar_url' => ['nullable', 'url:http,https', 'max:2048'],
            'website_url' => ['nullable', 'url:http,https', 'max:2048'],
            'is_public' => ['required', 'boolean'],
        ]);
        $slug = Str::slug($data['slug'] ?: $data['display_name']);
        if (AuthorProfile::query()->where('slug', $slug)->whereKeyNot($author->id)->exists()) {
            return back()->withErrors(['display_name' => 'An author profile with this slug already exists.']);
        }
        $author->update([...$data, 'slug' => $slug]);
        $audit->record('publishing.author.updated', $author, ['display_name' => $author->display_name]);
        return back()->with('success', 'Author profile updated.');
    }

    public function destroy(AuthorProfile $author, AuditManager $audit): RedirectResponse
    {
        $audit->record('publishing.author.deleted',$author,['display_name'=>$author->display_name]); $author->delete(); return back()->with('success','Author profile deleted.');
    }
}
