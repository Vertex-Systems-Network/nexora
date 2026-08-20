<?php

declare(strict_types=1);

namespace App\Nexora\Discovery\Analytics;

use Illuminate\Http\Request;

final class PrivacyIdentity
{
    /** @return array{visitor_hash:?string,session_hash:?string} */
    public function forRequest(Request $request): array
    {
        if ($request->header('Sec-GPC') === '1' || $request->header('DNT') === '1') {
            return ['visitor_hash'=>null,'session_hash'=>null];
        }
        $key = (string) config('app.key', 'nexora');
        $visitorInput = implode('|', [(string) $request->ip(), (string) $request->userAgent(), now()->format('Y-m-d')]);
        $sessionId = $request->hasSession() ? (string) $request->session()->getId() : '';
        return [
            'visitor_hash'=>hash_hmac('sha256', $visitorInput, $key),
            'session_hash'=>$sessionId !== '' ? hash_hmac('sha256', $sessionId, $key) : null,
        ];
    }
}
