<?php

declare(strict_types=1);

namespace App\Nexora\Media\Services;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

final class MediaUploadPolicy
{
    private const DEFAULT_PRODUCT_MAX_BYTES = 50 * 1024 * 1024;

    /** @var array<string,array{type:string,extension:string}> */
    private const MIME = [
        'image/jpeg' => ['type'=>'image','extension'=>'jpg'],
        'image/png' => ['type'=>'image','extension'=>'png'],
        'image/webp' => ['type'=>'image','extension'=>'webp'],
        'image/gif' => ['type'=>'image','extension'=>'gif'],
        'image/avif' => ['type'=>'image','extension'=>'avif'],
        'video/mp4' => ['type'=>'video','extension'=>'mp4'],
        'video/webm' => ['type'=>'video','extension'=>'webm'],
        'video/quicktime' => ['type'=>'video','extension'=>'mov'],
        'audio/mpeg' => ['type'=>'audio','extension'=>'mp3'],
        'audio/ogg' => ['type'=>'audio','extension'=>'ogg'],
        'audio/wav' => ['type'=>'audio','extension'=>'wav'],
        'audio/x-wav' => ['type'=>'audio','extension'=>'wav'],
        'application/pdf' => ['type'=>'document','extension'=>'pdf'],
        'text/plain' => ['type'=>'document','extension'=>'txt'],
        'text/csv' => ['type'=>'document','extension'=>'csv'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['type'=>'document','extension'=>'docx'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['type'=>'document','extension'=>'xlsx'],
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['type'=>'document','extension'=>'pptx'],
    ];

    /** @return array{mime:string,type:string,extension:string,size:int,width:?int,height:?int} */
    public function inspect(UploadedFile $file): array
    {
        if (! $file->isValid()) throw new InvalidArgumentException('The uploaded file did not complete successfully.');

        // getRealPath() may return false for otherwise valid PHP temporary uploads on some Windows stacks.
        $sourcePath = $file->getPathname();
        if ($sourcePath === '' || ! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw new InvalidArgumentException('The server cannot read the temporary upload. Check PHP upload_tmp_dir permissions and try again.');
        }

        $size = (int) ($file->getSize() ?: 0);
        $maximum = $this->effectiveMaxBytes();
        if ($size <= 0 || $size > $maximum) {
            throw new InvalidArgumentException('Media uploads must be between 1 byte and '.max(1, (int) floor($maximum / 1024 / 1024)).' MB on this server.');
        }

        try {
            $mime = strtolower((string) ($file->getMimeType() ?: ''));
        } catch (\Throwable) {
            throw new InvalidArgumentException('The server could not inspect the uploaded file type. Ensure the PHP fileinfo extension is enabled.');
        }

        $definition = self::MIME[$mime] ?? null;
        if (! $definition) throw new InvalidArgumentException("Unsupported media type [{$mime}]. Executable and active-content uploads are blocked.");

        $width = null; $height = null;
        if ($definition['type'] === 'image') {
            $dimensions = @getimagesize($sourcePath);
            if (! is_array($dimensions)) throw new InvalidArgumentException('The image payload is invalid, unsupported by this PHP image build, or unreadable.');
            $width = (int) ($dimensions[0] ?? 0); $height = (int) ($dimensions[1] ?? 0);
            if ($width < 1 || $height < 1) throw new InvalidArgumentException('The image dimensions are invalid.');
            if (($width * $height) > 60_000_000) throw new InvalidArgumentException('The image dimensions exceed Nexora safe-processing limits.');
        }
        return ['mime'=>$mime,'type'=>$definition['type'],'extension'=>$definition['extension'],'size'=>$size,'width'=>$width,'height'=>$height];
    }

    public function effectiveMaxBytes(): int
    {
        $limits = [max(1024, (int) config('nexora-transfers.media.max_upload_bytes', self::DEFAULT_PRODUCT_MAX_BYTES))];
        foreach (['upload_max_filesize', 'post_max_size'] as $key) {
            $parsed = $this->iniBytes((string) ini_get($key));
            if ($parsed > 0) $limits[] = $parsed;
        }
        $max = min($limits);
        // Leave multipart/form-data overhead headroom when post_max_size is the limiting factor.
        return max(1024, $max - min(262_144, (int) floor($max * 0.02)));
    }

    public function effectiveMaxKilobytes(): int
    {
        return max(1, (int) floor($this->effectiveMaxBytes() / 1024));
    }

    /** @return list<string> */
    public function acceptedMimes(): array { return array_keys(self::MIME); }

    private function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') return 0;
        if (is_numeric($value)) return max(0, (int) $value);
        $unit = strtolower(substr($value, -1));
        $number = (float) substr($value, 0, -1);
        return match ($unit) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => max(0, (int) $value),
        };
    }
}
