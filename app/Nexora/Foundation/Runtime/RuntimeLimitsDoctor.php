<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Runtime;

final class RuntimeLimitsDoctor
{
    /** @return array{status:string,checks:list<array{key:string,status:string,detail:string>>,recommended_worker_command:string} */
    public function inspect(): array
    {
        $checks = [];
        $failed = false;

        $add = static function (string $key, bool $ok, string $detail) use (&$checks, &$failed): void {
            $checks[] = ['key'=>$key, 'status'=>$ok ? 'pass' : 'fail', 'detail'=>$detail];
            if (! $ok) $failed = true;
        };

        $memory = $this->iniBytes('memory_limit');
        $minimumMemory = (int) config('nexora-runtime.php.minimum_memory_bytes', 536_870_912);
        $add('php.memory-limit', $memory >= $minimumMemory, $this->formatIni('memory_limit', $memory).'; required >= '.$this->formatBytes($minimumMemory));

        $post = $this->iniBytes('post_max_size');
        $minimumPost = (int) config('nexora-runtime.php.minimum_post_bytes', 67_108_864);
        $add('php.post-max-size', $post >= $minimumPost, $this->formatIni('post_max_size', $post).'; required >= '.$this->formatBytes($minimumPost));

        $upload = $this->iniBytes('upload_max_filesize');
        $minimumUpload = (int) config('nexora-runtime.php.minimum_upload_bytes', 67_108_864);
        $add('php.upload-max-filesize', $upload >= $minimumUpload, $this->formatIni('upload_max_filesize', $upload).'; required >= '.$this->formatBytes($minimumUpload));

        $requestMax = (int) config('nexora-runtime.http.max_body_bytes', 67_108_864);
        $add('http.request-ceiling-vs-php-post', $requestMax <= $post, $this->formatBytes($requestMax).' application ceiling; PHP post_max_size '.$this->formatBytes($post));
        $add('http.request-ceiling-vs-php-upload', $requestMax <= $upload, $this->formatBytes($requestMax).' application ceiling; PHP upload_max_filesize '.$this->formatBytes($upload));

        $execution = (int) ini_get('max_execution_time');
        $minimumExecution = (int) config('nexora-runtime.php.minimum_execution_seconds', 120);
        // CLI commonly reports 0 (unlimited). Web SAPI still needs operator validation through the target environment.
        $add('php.max-execution-time', $execution === 0 || $execution >= $minimumExecution, $execution === 0 ? '0 (CLI unlimited)' : $execution.'s; required >= '.$minimumExecution.'s');

        $inputTime = (int) ini_get('max_input_time');
        $minimumInput = (int) config('nexora-runtime.php.minimum_input_seconds', 60);
        $add('php.max-input-time', $inputTime === -1 || $inputTime === 0 || $inputTime >= $minimumInput, $inputTime.'s; required >= '.$minimumInput.'s or an accepted inherited/unlimited CLI value');

        $inputVars = (int) ini_get('max_input_vars');
        $minimumInputVars = (int) config('nexora-runtime.php.minimum_input_vars', 3000);
        $add('php.max-input-vars', $inputVars >= $minimumInputVars, $inputVars.'; required >= '.$minimumInputVars);

        $fileUploads = (int) ini_get('max_file_uploads');
        $minimumFileUploads = (int) config('nexora-runtime.php.minimum_file_uploads', 20);
        $add('php.max-file-uploads', $fileUploads >= $minimumFileUploads, $fileUploads.'; required >= '.$minimumFileUploads);

        $trustedProxies = (array) config('nexora-runtime.http.trusted_proxies', []);
        $proxyValid = true;
        foreach ($trustedProxies as $proxy) {
            $proxy = trim((string) $proxy);
            if ($proxy === '' || $proxy === '*' || str_contains($proxy, '://')) $proxyValid = false;
        }
        $add('http.trusted-proxies', $proxyValid, $trustedProxies === [] ? 'none configured; forwarded headers remain untrusted' : implode(', ', $trustedProxies));

        $maxJobTimeout = (int) config('nexora-runtime.queue.max_job_timeout_seconds', 1800);
        $margin = (int) config('nexora-runtime.queue.retry_after_margin_seconds', 60);
        foreach (['database','redis','beanstalkd'] as $connection) {
            $retryAfter = (int) config("queue.connections.{$connection}.retry_after", 0);
            $ok = $retryAfter > ($maxJobTimeout + $margin - 1);
            $add('queue.retry-after:'.$connection, $ok, $retryAfter.'s; must be >= '.($maxJobTimeout + $margin).'s to exceed the longest job timeout');
        }

        $workerTimeout = (int) config('nexora-runtime.queue.worker_timeout_seconds', 1800);
        $workerMaxTime = (int) config('nexora-runtime.queue.worker_max_time_seconds', 3600);
        $workerMemory = (int) config('nexora-runtime.queue.worker_restart_memory_mb', 384);
        $add('queue.worker-timeout', $workerTimeout >= $maxJobTimeout, $workerTimeout.'s worker timeout; longest job timeout '.$maxJobTimeout.'s');
        $add('queue.worker-max-time', $workerMaxTime > $workerTimeout, $workerMaxTime.'s max worker lifetime; timeout '.$workerTimeout.'s');
        $add('queue.worker-memory', $workerMemory >= 128, $workerMemory.' MiB graceful restart threshold');

        $command = sprintf(
            'php artisan queue:work --sleep=%d --timeout=%d --max-time=%d --memory=%d --tries=0',
            (int) config('nexora-runtime.queue.worker_sleep_seconds', 1),
            $workerTimeout,
            $workerMaxTime,
            $workerMemory,
        );

        return ['status'=>$failed ? 'fail' : 'pass', 'checks'=>$checks, 'recommended_worker_command'=>$command];
    }

    private function iniBytes(string $key): int
    {
        $raw = trim((string) ini_get($key));
        if ($raw === '' || $raw === '-1') return $raw === '-1' ? PHP_INT_MAX : 0;
        if (ctype_digit($raw)) return (int) $raw;
        $unit = strtolower(substr($raw, -1));
        $value = (float) substr($raw, 0, -1);
        return (int) round($value * match ($unit) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        });
    }

    private function formatIni(string $key, int $bytes): string
    {
        $raw = trim((string) ini_get($key));
        return $raw.' ('.$this->formatBytes($bytes).')';
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === PHP_INT_MAX) return 'unlimited';
        if ($bytes >= 1024 ** 3) return number_format($bytes / (1024 ** 3), 2).' GiB';
        if ($bytes >= 1024 ** 2) return number_format($bytes / (1024 ** 2), 2).' MiB';
        return number_format($bytes / 1024, 2).' KiB';
    }
}
