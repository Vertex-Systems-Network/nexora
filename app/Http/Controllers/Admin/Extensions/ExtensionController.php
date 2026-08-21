<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Extensions;

use App\Http\Controllers\Controller;
use App\Models\Capability;
use App\Models\Extension;
use App\Models\ExtensionVersion;
use App\Models\MarketplaceCatalogItem;
use App\Models\MarketplaceSource;
use App\Models\SupplyChainArtifact;
use App\Nexora\Automation\Services\WebhookUrlPolicy;
use App\Nexora\Extensions\Services\ExtensionLifecycleManager;
use App\Nexora\Extensions\Services\ExtensionPackageInstaller;
use App\Nexora\Extensions\Services\MarketplaceCatalogService;
use App\Nexora\Extensions\Services\MarketplacePackageStager;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final class ExtensionController extends Controller
{
    public function index(Request $request): Response
    {
        $extensions = Extension::query()->with(['publisher:id,name,key_id,trust_tier,status'])->withCount('versions')->latest('installed_at')->paginate(20)->withQueryString()->through(static fn (Extension $e) => [
            'id' => $e->id, 'identifier' => $e->identifier, 'name' => $e->name, 'type' => $e->type, 'status' => $e->status, 'current_version' => $e->current_version, 'versions_count' => $e->versions_count,
            'description' => $e->description, 'publisher' => $e->publisher?->name, 'trust_tier' => $e->publisher?->trust_tier, 'installed_at' => $e->installed_at?->toIso8601String(),
        ]);
        $eligible = SupplyChainArtifact::query()->with(['scan:id,decision,manifest', 'publisher:id,name,trust_tier,status'])->whereHas('scan', fn ($q) => $q->where('decision', 'allow'))->latest()->limit(30)->get()->filter(static function (SupplyChainArtifact $a): bool {
            $type = (string) ($a->scan?->manifest['type'] ?? '');
            return in_array($type, ['extension', 'app', 'integration', 'studio-pack'], true);
        })->map(static fn (SupplyChainArtifact $a) => [
            'id' => $a->id, 'identifier' => $a->package_identifier ?: ($a->scan?->manifest['id'] ?? 'Unknown package'), 'name' => $a->scan?->manifest['name'] ?? $a->package_identifier,
            'version' => $a->package_version ?: ($a->scan?->manifest['version'] ?? '—'), 'type' => $a->scan?->manifest['type'] ?? 'extension', 'trust_tier' => $a->trust_tier, 'signature_status' => $a->signature_status,
            'publisher' => $a->publisher?->name, 'content_sha256' => $a->content_sha256,
        ])->values();
        $sources = MarketplaceSource::query()->withCount('items')->orderBy('name')->get()->map(static fn (MarketplaceSource $s) => [
            'id' => $s->id, 'name' => $s->name, 'base_url' => $s->base_url, 'status' => $s->status, 'trusted_only' => $s->trusted_publishers_only, 'items_count' => $s->items_count,
            'last_synced_at' => $s->last_synced_at?->toIso8601String(), 'last_error' => $s->last_error,
        ]);
        $freshSource = static fn ($query) => $query->where('status', 'active')->whereNotNull('last_synced_at');
        $catalog = MarketplaceCatalogItem::query()->with('source:id,name,status,last_synced_at')->whereHas('source', $freshSource)->latest('synced_at')->limit(100)->get()->map(static fn (MarketplaceCatalogItem $i) => [
            'id' => $i->id, 'identifier' => $i->package_identifier, 'name' => $i->name, 'type' => $i->type, 'version' => $i->latest_version, 'description' => $i->description,
            'publisher_key_id' => $i->publisher_key_id, 'source' => $i->source?->name, 'synced_at' => $i->synced_at?->toIso8601String(),
        ]);

        return Inertia::render('Admin/Extensions/Index', [
            'extensions' => $extensions,
            'eligibleArtifacts' => $eligible,
            'sources' => $sources,
            'catalog' => $catalog,
            'summary' => [
                'installed' => Extension::query()->where('status', '!=', 'uninstalled')->count(),
                'enabled' => Extension::query()->where('status', 'enabled')->count(),
                'versions' => ExtensionVersion::query()->count(),
                'catalog' => MarketplaceCatalogItem::query()->whereHas('source', $freshSource)->count(),
            ],
            'canManage' => $request->user()?->hasPermission('extensions.manage') ?? false,
            'canInstall' => $request->user()?->hasPermission('extensions.install') ?? false,
            'canManageMarketplace' => $request->user()?->hasPermission('marketplace.manage') ?? false,
        ]);
    }

    public function show(Request $request, Extension $extension): Response
    {
        $extension->load(['publisher:id,name,key_id,trust_tier,status', 'versions.dependencies', 'versions.artifact:id,trust_tier,signature_status,sandbox_profile', 'grants', 'events' => fn ($q) => $q->latest('created_at')->limit(50)]);
        $current = $extension->current_version ? $extension->versions->firstWhere('version', $extension->current_version) : $extension->versions->sortByDesc('installed_at')->first();
        $requested = array_values(array_unique(array_filter((array) ($current?->manifest['capabilities'] ?? []), 'is_string')));
        $knownCapabilities = Capability::query()->whereIn('slug', $requested)->get(['slug', 'name', 'group', 'risk_level', 'description'])->keyBy('slug');
        $capabilities = collect($requested)->map(function (string $slug) use ($knownCapabilities, $extension): array {
            /** @var Capability|null $capability */
            $capability = $knownCapabilities->get($slug);
            return [
                'slug' => $slug,
                'name' => $capability?->name ?? Str::headline(str_replace(['.', '-', '_'], ' ', $slug)),
                'group' => $capability?->group ?? 'unregistered',
                'risk' => $capability?->risk_level ?? 'unknown',
                'description' => $capability?->description ?? 'This capability is not registered by the current Nexora runtime and cannot be granted until its provider is installed.',
                'registered' => $capability !== null,
                'granted' => (bool) ($extension->grants->firstWhere('capability_slug', $slug)?->granted ?? false),
            ];
        })->values();

        return Inertia::render('Admin/Extensions/Show', [
            'extension' => ['id' => $extension->id, 'identifier' => $extension->identifier, 'name' => $extension->name, 'type' => $extension->type, 'status' => $extension->status, 'current_version' => $extension->current_version, 'description' => $extension->description, 'publisher' => $extension->publisher?->name, 'trust_tier' => $extension->publisher?->trust_tier],
            'versions' => $extension->versions->sortByDesc('installed_at')->values()->map(fn ($v) => ['id' => $v->id, 'version' => $v->version, 'state' => $v->state, 'runtime_mode' => $v->runtime_mode, 'migration_policy' => $v->migration_policy, 'schema_compatible_rollback' => $v->schema_compatible_rollback, 'compatibility_status' => $v->compatibility_status, 'trust_tier' => $v->artifact?->trust_tier, 'signature_status' => $v->artifact?->signature_status, 'installed_at' => $v->installed_at?->toIso8601String(), 'dependencies' => $v->dependencies->map(fn ($d) => ['identifier' => $d->dependency_identifier, 'constraint' => $d->version_constraint, 'optional' => $d->optional])->values()]),
            'capabilities' => $capabilities,
            'events' => $extension->events->map(fn ($e) => ['id' => $e->id, 'event' => $e->event, 'status' => $e->status, 'context' => $e->context, 'created_at' => $e->created_at?->toIso8601String()])->values(),
            'canManage' => $request->user()?->hasPermission('extensions.manage') ?? false,
        ]);
    }

    public function install(Request $request, SupplyChainArtifact $artifact, ExtensionPackageInstaller $installer, AuditManager $audit): RedirectResponse
    {
        try {
            $version = $installer->install($artifact, $request->user()?->id);
            $audit->record('extension.installed', $version, ['artifact_id' => $artifact->id, 'version' => $version->version]);
            return redirect()->route('admin.extensions.show', $version->extension_id)->with('success', 'Verified package installed. Review capability grants before enabling it.');
        } catch (Throwable $e) {
            report($e);
            return back()->with('error', $e->getMessage());
        }
    }

    public function capabilities(Request $request, Extension $extension, ExtensionLifecycleManager $lifecycle): RedirectResponse
    {
        $data = $request->validate(['capabilities' => ['array'], 'capabilities.*' => ['string', 'max:180']]);
        $lifecycle->grantCapabilities($extension, $data['capabilities'] ?? [], $request->user()?->id);
        return back()->with('success', 'Extension capability grants updated.');
    }

    public function enable(Request $request, Extension $extension, ExtensionLifecycleManager $lifecycle): RedirectResponse
    {
        try {
            $lifecycle->enable($extension, $request->user()?->id, $request->string('version')->toString() ?: null);
            return back()->with('success', 'Extension enabled.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function disable(Request $request, Extension $extension, ExtensionLifecycleManager $lifecycle): RedirectResponse
    {
        $lifecycle->disable($extension, $request->user()?->id);
        return back()->with('success', 'Extension disabled.');
    }

    public function rollback(Request $request, Extension $extension, ExtensionLifecycleManager $lifecycle): RedirectResponse
    {
        try {
            $lifecycle->rollback($extension, $request->user()?->id);
            return back()->with('success', 'Extension rolled back to the previous compatible version.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function uninstall(Request $request, Extension $extension, ExtensionLifecycleManager $lifecycle): RedirectResponse
    {
        try {
            $lifecycle->uninstall($extension, $request->user()?->id);
            return redirect()->route('admin.extensions.index')->with('success', 'Extension files removed and lifecycle history preserved.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function source(Request $request, WebhookUrlPolicy $urls, AuditManager $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'base_url' => ['required', 'url', 'max:700', 'unique:nx_marketplace_sources,base_url'],
            'trusted_publishers_only' => ['boolean'],
        ]);

        try {
            $baseUrl = rtrim((string) $data['base_url'], '/');
            $urls->assertAllowed($baseUrl.'/nexora-marketplace.json', true);
            $source = MarketplaceSource::query()->create([
                'id' => (string) Str::uuid(),
                'name' => $data['name'],
                'base_url' => $baseUrl,
                'status' => 'active',
                'trusted_publishers_only' => $data['trusted_publishers_only'] ?? true,
                'created_by' => $request->user()?->id,
            ]);
            $audit->record('marketplace.source.created', $source, ['trusted_publishers_only' => $source->trusted_publishers_only]);
            return back()->with('success', 'Marketplace catalog source added. Synchronize it before staging packages.');
        } catch (Throwable $e) {
            return back()->withErrors(['base_url' => $e->getMessage()]);
        }
    }

    public function sourceStatus(Request $request, MarketplaceSource $source, AuditManager $audit): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'paused'])]]);
        $next = (string) $data['status'];
        if ($source->status === $next) {
            return back()->with('success', 'Marketplace source status is already '.strtolower($next).'.');
        }

        $attributes = ['status' => $next];
        if ($next === 'active') {
            $attributes['last_synced_at'] = null;
            $attributes['last_error'] = null;
        }
        $source->forceFill($attributes)->save();
        $audit->record('marketplace.source.status_changed', $source, [
            'status' => $next,
            'fresh_sync_required' => $next === 'active',
        ]);

        return back()->with('success', $next === 'active' ? 'Marketplace source resumed. Synchronize it before staging packages.' : 'Marketplace source paused. Its catalog is hidden and staging is blocked.');
    }

    public function deleteSource(MarketplaceSource $source, AuditManager $audit): RedirectResponse
    {
        if ($source->isActive()) {
            return back()->with('error', 'Pause the Marketplace source before removing it.');
        }

        $audit->record('marketplace.source.deleted', $source, ['name' => $source->name, 'base_url' => $source->base_url]);
        $source->delete();
        return back()->with('success', 'Marketplace source and its local catalog cache were removed. Installed extensions are unchanged.');
    }

    public function sync(MarketplaceSource $source, MarketplaceCatalogService $catalog, AuditManager $audit): RedirectResponse
    {
        try {
            $count = $catalog->sync($source);
            $audit->record('marketplace.source.synced', $source, ['packages' => $count]);
            return back()->with('success', "Marketplace catalog synchronized: {$count} packages.");
        } catch (Throwable $e) {
            $source->forceFill(['last_error' => $e->getMessage()])->save();
            return back()->with('error', $e->getMessage());
        }
    }

    public function stage(Request $request, MarketplaceCatalogItem $item, MarketplacePackageStager $stager, AuditManager $audit): RedirectResponse
    {
        try {
            $package = $stager->stage($item, $request->user()?->id);
            $audit->record('marketplace.package.staged', $item, ['source_id' => $item->source_id, 'version' => $item->latest_version]);
            $scan = $package->scans()->latest()->firstOrFail();
            return redirect()->route('admin.security.sentinel.show', $scan)->with('success', 'Marketplace package downloaded into quarantine and scanned. Review Sentinel before installation.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
