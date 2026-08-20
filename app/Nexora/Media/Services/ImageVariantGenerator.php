<?php

declare(strict_types=1);

namespace App\Nexora\Media\Services;

use Illuminate\Support\Facades\Storage;

final class ImageVariantGenerator
{
    /** @return array<string,array{path:string,width:int,height:int,mime:string,size:int}> */
    public function generate(string $disk, string $path, string $mime, int $sourceWidth, int $sourceHeight): array
    {
        if (! extension_loaded('gd') || $sourceWidth < 1 || $sourceHeight < 1 || ($sourceWidth * $sourceHeight) > 24_000_000) return [];
        if (! in_array($mime, ['image/jpeg','image/png','image/webp'], true) || ! function_exists('imagewebp')) return [];
        try {
            $size=(int)Storage::disk($disk)->size($path);
            if ($size < 1 || $size > (int)config('nexora-transfers.media.variant_decode_max_bytes',20_971_520)) return [];
            $bytes = Storage::disk($disk)->get($path);
        } catch (\Throwable) { return []; }
        $source = @imagecreatefromstring($bytes);
        unset($bytes);
        if (! $source) return [];

        $variants = []; $created=[];
        $base = preg_replace('/\.[^.]+$/', '', $path) ?: $path;
        try {
            foreach ([480, 960, 1440, 1920] as $targetWidth) {
                if ($sourceWidth <= $targetWidth) continue;
                $targetHeight = max(1, (int) round($sourceHeight * ($targetWidth / $sourceWidth)));
                $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
                if (! $canvas) continue;
                imagealphablending($canvas, false); imagesavealpha($canvas, true);
                imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
                ob_start(); imagewebp($canvas, null, 82); $encoded = ob_get_clean(); imagedestroy($canvas);
                if (! is_string($encoded) || $encoded === '') continue;
                $variantPath = $base.'-'.$targetWidth.'w.webp';
                if (! Storage::disk($disk)->put($variantPath, $encoded, ['visibility'=>'public'])) throw new \RuntimeException('Unable to persist generated media variant.');
                $created[]=$variantPath;
                $variants[(string) $targetWidth] = ['path'=>$variantPath,'width'=>$targetWidth,'height'=>$targetHeight,'mime'=>'image/webp','size'=>strlen($encoded)];
            }
        } catch (\Throwable) {
            if ($created!==[]) Storage::disk($disk)->delete($created);
            return [];
        } finally { imagedestroy($source); }
        return $variants;
    }
}
