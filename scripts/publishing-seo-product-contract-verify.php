<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) { $errors[] = "Required Publishing/SEO source file missing: {$relative}"; return ''; }
    $contents = file_get_contents($path);
    if ($contents === false) { $errors[] = "Unable to read Publishing/SEO source file: {$relative}"; return ''; }
    return $contents;
};

$articleController = $read('app/Http/Controllers/Admin/Publishing/ArticleController.php');
$articleSettings = $read('resources/js/admin/pages/Admin/Publishing/ArticleSettings.tsx');
$seoController = $read('app/Http/Controllers/Admin/Seo/DocumentSeoController.php');
$seoPage = $read('resources/js/admin/pages/Admin/Seo/Document.tsx');
$seoManager = $read('app/Nexora/Seo/Services/SeoManager.php');
$seoContract = $read('app/Nexora/Seo/Contracts/SeoManagerContract.php');
$themePage = $read('app/Http/Controllers/Public/ThemePageController.php');
$blog = $read('app/Http/Controllers/Public/BlogController.php');
$visibility = $read('app/Nexora/Publishing/Services/PublicDocumentVisibility.php');
$related = $read('app/Nexora/Publishing/Services/RelatedContentService.php');
$sitemap = $read('app/Nexora/Seo/Sitemap/SitemapService.php');
$publishingTest = $read('tests/Feature/Publishing/BlogPublishingFlowTest.php');
$seoTest = $read('tests/Feature/Seo/SeoCoreFlowTest.php');

if ($articleController !== '' && str_contains($articleController, "limit(250)")) {
    $errors[] = 'Publishing settings must not preload a fixed 250-image Media Library dropdown.';
}
foreach ([
    "'hero_media' => \$this->mediaSelection(\$heroMedia)" => 'current canonical hero selection payload',
    "new TenantExists('nx_media_assets')" => 'tenant-safe hero media validation',
    "->where('visibility', 'public')" => 'public hero image visibility guard',
] as $needle => $label) if ($articleController !== '' && ! str_contains($articleController, $needle)) $errors[] = "Publishing controller contract missing: {$label}.";

foreach ([
    'MediaPicker' => 'shared MediaPicker integration',
    'buttonLabel="Choose hero image"' => 'searchable hero selection UX',
    'hero_media_id' => 'canonical hero asset ID persistence',
    'allowClear' => 'hero media removal UX',
] as $needle => $label) if ($articleSettings !== '' && ! str_contains($articleSettings, $needle)) $errors[] = "Publishing UI contract missing: {$label}.";

foreach ([
    "'social_image_media_id'" => 'canonical social image asset request field',
    "'image_media_id' => \$socialAsset?->id" => 'social media ID persistence in SEO social payload',
    "\$mediaUsage->assign(\$socialAsset, 'document', (int) \$document->id, 'social_image')" => 'social image usage tracking',
    "new TenantExists('nx_media_assets')" => 'tenant-safe social media validation',
] as $needle => $label) if ($seoController !== '' && ! str_contains($seoController, $needle)) $errors[] = "SEO controller contract missing: {$label}.";

foreach ([
    'MediaPicker' => 'shared social MediaPicker integration',
    'social_image_media_id' => 'social canonical media state',
    'buttonLabel="Choose social image"' => 'social media selection UX',
    'External social image URL' => 'external social image fallback',
] as $needle => $label) if ($seoPage !== '' && ! str_contains($seoPage, $needle)) $errors[] = "SEO UI contract missing: {$label}.";

foreach ([
    "'social' => [" => 'resolved public social payload',
    "isset(\$social['image_media_id'])" => 'canonical social image ID resolution',
    "->where('visibility', 'public')" => 'social asset public visibility guard',
    "'twitter_card' => \$image ? 'summary_large_image' : 'summary'" => 'resolved Twitter card mode',
] as $needle => $label) if ($seoManager !== '' && ! str_contains($seoManager, $needle)) $errors[] = "SEO manager contract missing: {$label}.";
if ($seoContract !== '' && ! str_contains($seoContract, 'social:array<string,mixed>')) $errors[] = 'SEO public contract must expose resolved social metadata.';

foreach ([
    "array_values(array_filter(array_map('strval', (array) (\$robots['directives'] ?? []))))" => 'additional robots directive output',
    '<meta property="og:title"' => 'Open Graph title output',
    '<meta property="og:image"' => 'Open Graph image output',
    '<meta name="twitter:card"' => 'Twitter card output',
    "url(\$this->documentUrl(\$document))" => 'route-correct canonical fallback',
    '$this->visibility->apply($featuredQuery)' => 'protected featured-content filtering',
    '$this->visibility->apply($latestQuery)' => 'protected latest-content filtering',
    '$this->visibility->apply($seriesQuery)' => 'protected series navigation filtering',
] as $needle => $label) if ($themePage !== '' && ! str_contains($themePage, $needle)) $errors[] = "Public theme contract missing: {$label}.";

foreach ([
    "MembershipAccessPolicy::query()" => 'membership policy source',
    "->where('resource_type', 'document')" => 'document-only visibility policy',
    "->where('active', true)" => 'active policy filtering',
    "whereNotIn('nx_documents.id', \$protected)" => 'qualified public visibility exclusion',
] as $needle => $label) if ($visibility !== '' && ! str_contains($visibility, $needle)) $errors[] = "Public visibility contract missing: {$label}.";

if ($blog !== '' && ! str_contains($blog, '$this->visibility->apply($query)->limit(24)')) $errors[] = 'Blog/taxonomy/author/series archives must apply public membership visibility before listing.';
if ($related !== '' && ! str_contains($related, '$this->visibility->apply($query)->get()')) $errors[] = 'Related-content suggestions must exclude membership-protected documents.';
foreach ([
    '$this->visibility->protectedDocumentIds()' => 'shared protected document set',
    '$this->visibility->apply($publishedQuery)' => 'document sitemap visibility filter',
    "whereNotIn('nx_documents.id', \$protected)" => 'archive sitemap visibility filter',
] as $needle => $label) if ($sitemap !== '' && ! str_contains($sitemap, $needle)) $errors[] = "Sitemap visibility contract missing: {$label}.";

foreach ([
    'test_membership_protected_published_content_is_not_leaked_in_public_home_or_blog_archives' => 'public archive leak regression test',
    'assertDontSee($protected->title)' => 'protected title non-disclosure assertion',
] as $needle => $label) if ($publishingTest !== '' && ! str_contains($publishingTest, $needle)) $errors[] = "Publishing acceptance-test contract missing: {$label}.";

foreach ([
    'test_administrator_can_save_document_seo_and_public_runtime_emits_resolved_metadata' => 'SEO runtime acceptance test',
    'content="index,follow,noarchive"' => 'additional robots directive assertion',
    'property="og:title" content="Social SEO Core Test"' => 'Open Graph assertion',
    'name="twitter:card" content="summary_large_image"' => 'Twitter card assertion',
] as $needle => $label) if ($seoTest !== '' && ! str_contains($seoTest, $needle)) $errors[] = "SEO acceptance-test contract missing: {$label}.";

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Publishing + SEO Product Contract] FAILED\n - ".implode("\n - ", $errors)."\n");
    exit(1);
}

fwrite(STDOUT, "[Nexora Publishing + SEO Product Contract] PASS — scalable Media Library reuse, canonical social metadata, OG/Twitter/robots output, public membership visibility, sitemap filtering and acceptance-test source are aligned.\n");