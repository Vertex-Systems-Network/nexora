<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

final class SentinelFailureReference
{
    /** @return array{reference:string,message:string,class_fingerprint:string} */
    public function for(Throwable $exception, string $scope): array
    {
        $scope = trim($scope);
        $seed = $scope.'|'.$exception::class.'|'.(string) $exception->getCode();
        $reference = 'SNT-'.strtoupper(substr(hash('sha256', $seed), 0, 16));

        return [
            'reference' => $reference,
            'message' => 'Sentinel could not complete the security scan. Review server logs with reference '.$reference.'.',
            'class_fingerprint' => hash('sha256', $exception::class),
        ];
    }

    /**
     * Log the private diagnostic with the same opaque reference shown to operators.
     * Raw throwable details stay in server logs and never become durable Admin/audit payload.
     *
     * @param array<string, scalar|null> $context
     * @return array{reference:string,message:string,class_fingerprint:string}
     */
    public function report(Throwable $exception, string $scope, array $context = []): array
    {
        $failure = $this->for($exception, $scope);

        Log::error('Sentinel security scan failed.', array_merge($context, [
            'error_reference' => $failure['reference'],
            'exception_class_sha256' => $failure['class_fingerprint'],
            'exception' => $exception,
        ]));

        return $failure;
    }
}
