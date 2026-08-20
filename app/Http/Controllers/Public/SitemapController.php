<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Nexora\Seo\Sitemap\SitemapService;
use Illuminate\Http\Response;

final class SitemapController extends Controller
{
    public function __invoke(SitemapService $sitemap): Response
    {
        return response($sitemap->xml(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
