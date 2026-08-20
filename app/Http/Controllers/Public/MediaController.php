<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MediaController extends Controller
{
    public function __invoke(Request $request, MediaAsset $asset, ?string $variant = null): StreamedResponse
    {
        abort_if($asset->trashed() || $asset->visibility !== 'public', 404);
        $path=$asset->path; $mime=$asset->mime_type; $etag=$asset->checksum_sha256;
        if ($variant !== null) {
            $definition=(array) (($asset->variants ?? [])[$variant] ?? []);
            abort_unless(is_string($definition['path'] ?? null),404);
            $path=$definition['path']; $mime=(string) ($definition['mime'] ?? 'image/webp'); $etag=hash('sha256',$asset->checksum_sha256.':'.$variant);
        }
        abort_unless(Storage::disk($asset->disk)->exists($path),404);
        if (trim((string) $request->header('If-None-Match'),'"') === $etag) abort(304);
        return Storage::disk($asset->disk)->response($path, $asset->original_name, [
            'Content-Type'=>$mime,'Cache-Control'=>'public, max-age=31536000, immutable','ETag'=>'"'.$etag.'"','X-Content-Type-Options'=>'nosniff',
            'Content-Security-Policy'=>"default-src 'none'; sandbox",
        ]);
    }
}
