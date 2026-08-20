<?php

declare(strict_types=1);

namespace App\Nexora\Automation\Services;

final class WebhookSigner
{
    public function signature(string $secret, string $timestamp, string $body): string
    {
        return 'v1='.hash_hmac('sha256', $timestamp.'.'.$body, $secret);
    }

    public function verify(string $secret, string $timestamp, string $body, string $provided): bool
    {
        return hash_equals($this->signature($secret, $timestamp, $body), trim($provided));
    }
}
