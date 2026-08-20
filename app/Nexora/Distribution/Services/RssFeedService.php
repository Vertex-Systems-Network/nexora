<?php

declare(strict_types=1);

namespace App\Nexora\Distribution\Services;

use App\Models\Document;
use App\Nexora\Themes\Services\DocumentHtmlRenderer;

final readonly class RssFeedService
{
    public function __construct(private DocumentHtmlRenderer $renderer) {}

    public function xml(): string
    {
        $siteName = (string) config('app.name', 'Nexora');
        $siteUrl = rtrim((string) config('app.url', ''), '/');
        $documents = Document::query()->whereIn('type', ['article','blog_post'])->where('status', 'published')
            ->whereNotNull('slug')->latest('published_at')->limit(50)->get();
        $rows = ['<?xml version="1.0" encoding="UTF-8"?>','<rss version="2.0"><channel>'];
        $rows[] = '<title>'.$this->xmlText($siteName).'</title>';
        $rows[] = '<link>'.$this->xmlText($siteUrl !== '' ? $siteUrl.'/blog' : '/blog').'</link>';
        $rows[] = '<description>'.$this->xmlText($siteName.' publishing feed').'</description>';
        $rows[] = '<lastBuildDate>'.gmdate(DATE_RSS).'</lastBuildDate>';
        foreach ($documents as $document) {
            $url = ($siteUrl !== '' ? $siteUrl : '').($document->type === 'blog_post' ? '/blog/' : '/articles/').rawurlencode((string) $document->slug);
            $body = $this->renderer->render($document->content);
            $rows[] = '<item><title>'.$this->xmlText((string) $document->title).'</title><link>'.$this->xmlText($url).'</link><guid isPermaLink="true">'.$this->xmlText($url).'</guid>';
            if ($document->published_at) $rows[] = '<pubDate>'.$document->published_at->format(DATE_RSS).'</pubDate>';
            if ($document->excerpt) $rows[] = '<description>'.$this->xmlText((string) $document->excerpt).'</description>';
            $rows[] = '<content:encoded xmlns:content="http://purl.org/rss/1.0/modules/content/"><![CDATA['.$this->safeCdata($body).']]></content:encoded></item>';
        }
        $rows[] = '</channel></rss>';
        return implode("\n", $rows)."\n";
    }

    private function xmlText(string $value): string { return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8'); }
    private function safeCdata(string $value): string { return str_replace(']]>', ']]]]><![CDATA[>', $value); }
}
