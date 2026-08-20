<?php

declare(strict_types=1);

namespace App\Nexora\Discovery\Crawler;

use DOMDocument;
use DOMXPath;

final class PageInspector
{
    /** @return array{title:?string,meta_description:?string,canonical_url:?string,robots:?string,h1_count:int,word_count:int,internal_links:list<string>,external_links:list<string>,has_schema:bool,content_hash:string} */
    public function inspect(string $html, string $url, string $siteHost): array
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded) {
            return ['title'=>null,'meta_description'=>null,'canonical_url'=>null,'robots'=>null,'h1_count'=>0,'word_count'=>0,'internal_links'=>[],'external_links'=>[],'has_schema'=>false,'content_hash'=>hash('sha256', $html)];
        }

        $xpath = new DOMXPath($dom);
        $title = trim((string) ($xpath->query('//title')->item(0)?->textContent ?? '')) ?: null;
        $description = $this->attribute($xpath, '//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="description"]', 'content');
        $canonical = $this->attribute($xpath, '//link[translate(@rel,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="canonical"]', 'href');
        $robots = $this->attribute($xpath, '//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="robots"]', 'content');
        $h1Count = $xpath->query('//h1')->length;
        $hasSchema = $xpath->query('//script[@type="application/ld+json"]')->length > 0;
        $text = preg_replace('/\s+/u', ' ', trim((string) ($xpath->query('//body')->item(0)?->textContent ?? ''))) ?: '';
        $words = $text === '' ? 0 : count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: []);
        $internal = [];
        $external = [];
        foreach ($xpath->query('//a[@href]') as $anchor) {
            $href = trim((string) $anchor->attributes?->getNamedItem('href')?->nodeValue);
            if ($href === '' || str_starts_with($href, '#') || preg_match('/^(mailto:|tel:|javascript:)/i', $href)) continue;
            $resolved = $this->resolveUrl($href, $url);
            if ($resolved === null) continue;
            $host = (string) parse_url($resolved, PHP_URL_HOST);
            if ($host !== '' && strcasecmp($host, $siteHost) === 0) $internal[] = $resolved;
            else $external[] = $resolved;
        }

        return [
            'title'=>$title,'meta_description'=>$description,'canonical_url'=>$canonical,'robots'=>$robots,
            'h1_count'=>$h1Count,'word_count'=>$words,'internal_links'=>array_values(array_unique($internal)),
            'external_links'=>array_values(array_unique($external)),'has_schema'=>$hasSchema,'content_hash'=>hash('sha256', $html),
        ];
    }

    private function attribute(DOMXPath $xpath, string $query, string $attribute): ?string
    {
        $node = $xpath->query($query)->item(0);
        $value = trim((string) $node?->attributes?->getNamedItem($attribute)?->nodeValue);
        return $value !== '' ? $value : null;
    }

    private function resolveUrl(string $href, string $base): ?string
    {
        if (filter_var($href, FILTER_VALIDATE_URL)) return $this->stripFragment($href);
        if (str_starts_with($href, '//')) {
            $scheme = (string) (parse_url($base, PHP_URL_SCHEME) ?: 'https');
            return $this->stripFragment($scheme.':'.$href);
        }
        $parts = parse_url($base);
        if (! is_array($parts) || empty($parts['host'])) return null;
        $origin = ($parts['scheme'] ?? 'https').'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        if (str_starts_with($href, '/')) return $this->stripFragment($origin.$href);
        $path = (string) ($parts['path'] ?? '/');
        $directory = rtrim(str_replace('\\', '/', dirname($path)), '/');
        return $this->stripFragment($origin.($directory !== '' ? $directory : '').'/'.$href);
    }

    private function stripFragment(string $url): string
    {
        return preg_replace('/#.*$/', '', $url) ?: $url;
    }
}
