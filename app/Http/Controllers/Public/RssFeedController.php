<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Nexora\Distribution\Services\RssFeedService;
use Illuminate\Http\Response;

final class RssFeedController extends Controller
{
    public function __invoke(RssFeedService $feeds): Response
    {
        return response($feeds->xml(), 200, ['Content-Type'=>'application/rss+xml; charset=UTF-8','Cache-Control'=>'public, max-age=300']);
    }
}
