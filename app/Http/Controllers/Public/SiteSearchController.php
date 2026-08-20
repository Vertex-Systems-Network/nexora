<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Nexora\Discovery\Analytics\AnalyticsRecorder;
use App\Nexora\Discovery\Search\SearchIndexer;
use App\Nexora\Foundation\Contracts\SettingsContract;
use App\Nexora\Themes\Contracts\ThemeRendererContract;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class SiteSearchController extends Controller
{
    public function __construct(
        private SearchIndexer $search,
        private AnalyticsRecorder $analytics,
        private ThemeRendererContract $themes,
        private SettingsContract $settings,
    ) {}

    public function __invoke(Request $request): Response
    {
        if (! filter_var($this->settings->get('search.public_enabled', true), FILTER_VALIDATE_BOOL)) abort(404);
        $query = trim($request->string('q')->toString());
        if ($query !== '') $request->validate(['q'=>['string','min:2','max:100']]);
        $results = $query === '' ? collect() : $this->search->search($query, true, 30);
        if ($query !== '') $this->analytics->search($request, $query, $results->count(), 'public');
        $siteName = (string) $this->settings->get('seo.site_name', $this->settings->get('app.name','Nexora'));
        $content = '<section class="nx-search"><header><h1>Search</h1></header><form method="get" action="/search"><label for="nx-public-search">Search this site</label><input id="nx-public-search" name="q" value="'.e($query).'" maxlength="100"><button type="submit">Search</button></form>';
        if ($query !== '') {
            $content .= '<p>'.e((string) $results->count()).' result'.($results->count()===1?'':'s').' for <strong>'.e($query).'</strong>.</p><div class="nx-search-results">';
            foreach ($results as $result) {
                $content .= '<article><h2><a href="'.e((string) $result['url_path']).'">'.e((string) $result['title']).'</a></h2>';
                if ($result['excerpt']) $content .= '<p>'.e((string) $result['excerpt']).'</p>';
                $content .= '</article>';
            }
            if ($results->isEmpty()) $content .= '<p class="nx-empty">No matching published content was found.</p>';
            $content .= '</div>';
        }
        $content .= '</section>';
        $title = $query !== '' ? 'Search: '.$query : 'Search';
        $html = $this->themes->render('home', [
            'site_name'=>$siteName,'page_title'=>$title,'tagline'=>'Search published site content.',
            'nx_head'=>'<title>'.e($title).' · '.e($siteName).'</title><meta name="robots" content="noindex,follow"><link rel="canonical" href="'.e(url('/search')).'">',
            'nx_schema'=>'','nx_content'=>$content,
        ]);
        return response($html)->header('Content-Type','text/html; charset=UTF-8');
    }
}
