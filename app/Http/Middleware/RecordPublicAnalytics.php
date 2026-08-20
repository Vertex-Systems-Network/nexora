<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Document;
use App\Nexora\Discovery\Analytics\AnalyticsRecorder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class RecordPublicAnalytics
{
    public function __construct(private AnalyticsRecorder $analytics) {}

    public function handle(Request $request, Closure $next): Response
    {
        $started = hrtime(true);
        $response = $next($request);
        if ($request->isMethod('GET') && str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            $duration = (int) round((hrtime(true) - $started) / 1_000_000);
            $document = $request->route('document');
            $resourceType = $document instanceof Document ? 'document' : null;
            $resourceId = $document instanceof Document ? (int) $document->id : null;
            $this->analytics->pageView($request, $response->getStatusCode(), $duration, $resourceType, $resourceId, [
                'route'=>(string) ($request->route()?->getName() ?? ''),
            ]);
        }
        return $response;
    }
}
