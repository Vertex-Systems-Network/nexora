<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Media;

use App\Nexora\Enterprise\Validation\TenantExists;
use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\MediaCollection;
use App\Models\MediaFolder;
use App\Nexora\Media\Contracts\MediaManagerContract;
use App\Nexora\Media\Services\MediaUploadPolicy;
use App\Http\Middleware\AssignRequestId;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class MediaController extends Controller
{
    public function index(Request $request, MediaManagerContract $media, MediaUploadPolicy $policy): Response|JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('type', '');
        $folderId = (int) $request->query('folder', 0);
        $picker = $request->boolean('picker');
        $view = $picker ? 'library' : (string) $request->query('view', 'library');
        $query = MediaAsset::query()->with(['folder:id,name','uploader:id,name'])->withCount('usages')->latest('id');
        if ($view === 'trash') $query->onlyTrashed();
        if ($search !== '') $query->where(fn ($q) => $q->where('original_name','like',"%{$search}%")->orWhere('title','like',"%{$search}%")->orWhere('alt_text','like',"%{$search}%"));
        if (in_array($type, ['image','video','audio','document'], true)) $query->where('media_type',$type);
        if ($folderId > 0) $query->where('folder_id',$folderId);

        if ($picker) {
            $limit = max(1, min(60, (int) $request->query('limit', 48)));
            $items = $query->limit($limit)->get()->map(fn (MediaAsset $asset): array => array_merge($media->present($asset), ['uploader'=>$asset->uploader?->name]));

            return response()->json([
                'assets'=>$items->values(),
                'filters'=>['search'=>$search,'type'=>$type,'folder'=>$folderId ?: ''],
                'limit'=>$limit,
            ]);
        }

        $assets = $query->paginate(24)->withQueryString();
        $assets->through(fn (MediaAsset $asset): array => array_merge($media->present($asset), ['uploader'=>$asset->uploader?->name]));

        return Inertia::render('Admin/Media/Index', [
            'assets'=>$assets,
            'filters'=>['search'=>$search,'type'=>$type,'folder'=>$folderId ?: '', 'view'=>$view],
            'folders'=>MediaFolder::query()->orderBy('sort_order')->orderBy('name')->get(['id','parent_id','name','slug'])->values(),
            'collections'=>MediaCollection::query()->withCount('assets')->orderBy('name')->get(['id','name','slug','description'])->values(),
            'summary'=>[
                'all'=>MediaAsset::withTrashed()->count(),'images'=>MediaAsset::query()->where('media_type','image')->count(),
                'documents'=>MediaAsset::query()->where('media_type','document')->count(),'trash'=>MediaAsset::onlyTrashed()->count(),
            ],
            'upload'=>[
                'max_mb'=>round($policy->effectiveMaxBytes() / 1024 / 1024, 1),
                'accepted'=>implode(',', $policy->acceptedMimes()).',.docx,.xlsx,.pptx',
                'storage_ready'=>is_dir(storage_path('app/public')) && is_writable(storage_path('app/public')),
            ],
        ]);
    }

    public function upload(Request $request, MediaManagerContract $media, MediaUploadPolicy $policy, AuditManager $audit): RedirectResponse
    {
        $data = $request->validate([
            'file'=>['required','file','max:'.$policy->effectiveMaxKilobytes()],
            'folder_id'=>['nullable','integer',new TenantExists('nx_media_folders')],
            'title'=>['nullable','string','max:255'],'alt_text'=>['nullable','string','max:500'],'caption'=>['nullable','string','max:2000'],
        ]);
        try {
            $asset = $media->upload($data['file'], isset($data['folder_id']) ? (int) $data['folder_id'] : null, $request->user()?->id, $data);
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['file'=>$exception->getMessage()]);
        } catch (Throwable $exception) {
            $requestId = (string) ($request->attributes->get(AssignRequestId::ATTRIBUTE) ?: 'unknown');
            Log::error('Nexora media upload failed.', [
                'request_id'=>$requestId,
                'user_id'=>$request->user()?->id,
                'original_name'=>$data['file']->getClientOriginalName(),
                'size'=>$data['file']->getSize(),
                'exception'=>$exception,
            ]);
            report($exception);
            return back()->withErrors(['file'=>"Nexora could not store this upload. Check storage/PHP media requirements and retry. Reference: {$requestId}"]);
        }

        try {
            $audit->record('media.asset.uploaded',$asset,['mime_type'=>$asset->mime_type,'size_bytes'=>$asset->size_bytes]);
        } catch (Throwable $exception) {
            report($exception);
            return back()->with('success','Media uploaded successfully.')->with('warning','The upload succeeded, but its audit entry could not be written.');
        }

        return back()->with('success','Media uploaded and inspected successfully.');
    }

    public function update(Request $request, MediaAsset $asset, AuditManager $audit): RedirectResponse
    {
        $data = $request->validate([
            'folder_id'=>['nullable','integer',new TenantExists('nx_media_folders')],'title'=>['nullable','string','max:255'],'alt_text'=>['nullable','string','max:500'],
            'caption'=>['nullable','string','max:2000'],'description'=>['nullable','string','max:5000'],
            'focal_x'=>['nullable','numeric','between:0,100'],'focal_y'=>['nullable','numeric','between:0,100'],
        ]);
        $asset->fill($data)->save();
        $audit->record('media.asset.updated',$asset,['fields'=>array_keys($data)]);
        return back()->with('success','Media metadata updated.');
    }

    public function destroy(MediaAsset $asset, MediaManagerContract $media, AuditManager $audit): RedirectResponse
    {
        $audit->record('media.asset.trashed',$asset,['title'=>$asset->title]); $media->delete($asset);
        return back()->with('success','Media moved to Trash. Existing usage records are preserved.');
    }

    public function restore(string $asset, MediaManagerContract $media, AuditManager $audit): RedirectResponse
    {
        $record = MediaAsset::withTrashed()->findOrFail((int) $asset); $media->restore($record);
        $audit->record('media.asset.restored',$record,[]); return back()->with('success','Media restored.');
    }

    public function forceDelete(string $asset, MediaManagerContract $media, AuditManager $audit): RedirectResponse
    {
        $record = MediaAsset::withTrashed()->findOrFail((int) $asset);
        if ($record->usages()->exists()) return back()->withErrors(['media'=>'This asset is still in use. Remove its references before permanent deletion.']);
        $audit->record('media.asset.deleted',$record,['checksum'=>$record->checksum_sha256]); $media->forceDelete($record);
        return back()->with('success','Media permanently deleted.');
    }

    public function folder(Request $request): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:180'],'parent_id'=>['nullable','integer',new TenantExists('nx_media_folders')]]);
        $base=Str::slug($data['name']) ?: 'folder'; $slug=$base; $i=2;
        while (MediaFolder::query()->where('parent_id',$data['parent_id'] ?? null)->where('slug',$slug)->exists()) $slug=$base.'-'.$i++;
        MediaFolder::query()->create(['uuid'=>(string) Str::uuid(),'name'=>$data['name'],'slug'=>$slug,'parent_id'=>$data['parent_id'] ?? null,'created_by'=>$request->user()?->id]);
        return back()->with('success','Media folder created.');
    }

    public function collection(Request $request): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:180'],'description'=>['nullable','string','max:2000']]);
        $base=Str::slug($data['name']) ?: 'collection'; $slug=$base; $i=2; while (MediaCollection::query()->where('slug',$slug)->exists()) $slug=$base.'-'.$i++;
        MediaCollection::query()->create(['uuid'=>(string) Str::uuid(),'name'=>$data['name'],'slug'=>$slug,'description'=>$data['description'] ?? null,'created_by'=>$request->user()?->id]);
        return back()->with('success','Media collection created.');
    }

    public function collectionAsset(Request $request, MediaCollection $collection): RedirectResponse
    {
        $data=$request->validate(['asset_id'=>['required','integer',Rule::exists('nx_media_assets','id')->whereNull('deleted_at')]]);
        $position=(int) ($collection->assets()->max('position') ?? 0)+1;
        $collection->assets()->syncWithoutDetaching([(int) $data['asset_id']=>['position'=>$position]]);
        return back()->with('success','Media added to collection.');
    }
}
