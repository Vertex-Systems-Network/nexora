<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Support;

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
}
