<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\QuarantinePackage;
use App\Models\SecurityScan;
use App\Models\SupplyChainArtifact;
use App\Nexora\Security\Audit\AuditManager;
use App\Nexora\Security\Sentinel\Support\QuarantineManager;
use App\Nexora\Security\Sentinel\Support\ScanRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final class SentinelController extends Controller
{
    public function __construct(
        private QuarantineManager $quarantine,
        private ScanRecorder $recorder,
        private AuditManager $audit,
    ) {
    }

    public function index(Request $request): Response
    {
        $scans = SecurityScan::query()
            ->with(['requester:id,name', 'quarantinePackage:id,original_name,status'])
            ->withCount('findings')
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(static fn (SecurityScan $scan): array => [
                'id' => $scan->id,
                'source_name' => $scan->source_name,
                'source_sha256' => $scan->source_sha256,
                'status' => $scan->status,
                'decision' => $scan->decision,
                'risk_score' => $scan->risk_score,
                'engine_version' => $scan->engine_version,
                'findings_count' => $scan->findings_count,
                'requested_by' => $scan->requester?->name,
                'created_at' => $scan->created_at?->toIso8601String(),
                'package_status' => $scan->quarantinePackage?->status,
            ]);

        $summary = [
            'total' => SecurityScan::query()->count(),
            'blocked' => SecurityScan::query()->where('decision', 'block')->count(),
            'review' => SecurityScan::query()->where('decision', 'review')->count(),
            'allowed' => SecurityScan::query()->where('decision', 'allow')->count(),
            'quarantined' => QuarantinePackage::query()->where('status', 'quarantined')->count(),
        ];

        return Inertia::render('Admin/Security/Sentinel/Index', [
            'scans' => $scans,
            'summary' => $summary,
            'upload' => [
                'maxKilobytes' => (int) config('sentinel.upload.max_kilobytes', 51_200),
                'extensions' => (array) config('sentinel.upload.extensions', ['zip']),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $maxKb = (int) config('sentinel.upload.max_kilobytes', 51_200);
        $validated = $request->validate([
            'package' => ['required', 'file', "max:{$maxKb}"],
        ]);

        $file = $validated['package'];
        if (strtolower($file->getClientOriginalExtension()) !== 'zip') {
            throw ValidationException::withMessages(['package' => 'Nexora Sentinel currently accepts ZIP packages only.']);
        }

        $package = $this->quarantine->store($file, $request->user()?->id);
        $this->audit->record('sentinel.package.quarantined', $package, [
            'name' => $package->original_name,
            'sha256' => $package->sha256,
            'size_bytes' => $package->size_bytes,
        ]);

        try {
            $scan = $this->recorder->scan($package, $request->user()?->id);
            $this->audit->record('sentinel.scan.completed', $scan, [
                'decision' => $scan->decision,
                'risk_score' => $scan->risk_score,
                'package_id' => $package->id,
            ]);

            return redirect()->route('admin.security.sentinel.show', $scan)
                ->with($scan->decision === 'allow' ? 'success' : 'warning', "Sentinel scan completed with decision: {$scan->decision}.");
        } catch (Throwable $exception) {
            report($exception);
            $scan = $package->scans()->latest()->first();
            $this->audit->record('sentinel.scan.failed', $scan ?? $package, ['error' => $exception->getMessage()]);

            if ($scan) {
                return redirect()->route('admin.security.sentinel.show', $scan)
                    ->with('error', 'Sentinel could not fully inspect the package. It remains quarantined and blocked.');
            }

            return back()->with('error', 'Package was quarantined, but the security scan could not start.');
        }
    }

    public function show(Request $request, SecurityScan $scan): Response
    {
        $severity = $request->string('severity')->toString();
        $findingsQuery = $scan->findings()->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low', 'info')")->orderBy('file_path')->orderBy('line_start');
        if (in_array($severity, ['critical', 'high', 'medium', 'low', 'info'], true)) {
            $findingsQuery->where('severity', $severity);
        }

        $findings = $findingsQuery->paginate(100)->withQueryString()->through(static fn ($finding): array => [
            'id' => $finding->id,
            'rule_id' => $finding->rule_id,
            'severity' => $finding->severity,
            'category' => $finding->category,
            'title' => $finding->title,
            'message' => $finding->message,
            'file_path' => $finding->file_path,
            'line_start' => $finding->line_start,
            'line_end' => $finding->line_end,
            'excerpt' => $finding->excerpt,
            'hard_block' => $finding->hard_block,
            'metadata' => $finding->metadata,
        ]);

        $scan->load(['requester:id,name,email', 'quarantinePackage:id,original_name,status,size_bytes,sha256,scanned_at']);
        $supplyChain = SupplyChainArtifact::query()->where('scan_id', $scan->id)->with(['publisher:id,name,key_id,trust_tier,status'])->withCount('components')->first();

        return Inertia::render('Admin/Security/Sentinel/Show', [
            'scan' => [
                'id' => $scan->id,
                'source_name' => $scan->source_name,
                'source_sha256' => $scan->source_sha256,
                'engine_version' => $scan->engine_version,
                'status' => $scan->status,
                'decision' => $scan->decision,
                'risk_score' => $scan->risk_score,
                'manifest' => $scan->manifest ?? [],
                'summary' => $scan->summary ?? [],
                'error' => $scan->error,
                'requested_by' => $scan->requester ? ['name' => $scan->requester->name, 'email' => $scan->requester->email] : null,
                'started_at' => $scan->started_at?->toIso8601String(),
                'completed_at' => $scan->completed_at?->toIso8601String(),
                'package' => $scan->quarantinePackage ? [
                    'id' => $scan->quarantinePackage->id,
                    'name' => $scan->quarantinePackage->original_name,
                    'status' => $scan->quarantinePackage->status,
                    'size_bytes' => $scan->quarantinePackage->size_bytes,
                    'sha256' => $scan->quarantinePackage->sha256,
                ] : null,
            ],
            'findings' => $findings,
            'filters' => ['severity' => $severity],
            'supplyChain' => $supplyChain ? [
                'id'=>$supplyChain->id,'signature_status'=>$supplyChain->signature_status,'provenance_status'=>$supplyChain->provenance_status,
                'trust_tier'=>$supplyChain->trust_tier,'sandbox_profile'=>$supplyChain->sandbox_profile,'components_count'=>$supplyChain->components_count,
                'content_sha256'=>$supplyChain->content_sha256,'verification_error'=>$supplyChain->verification_error,
                'publisher'=>$supplyChain->publisher ? ['name'=>$supplyChain->publisher->name,'key_id'=>$supplyChain->publisher->key_id,'trust_tier'=>$supplyChain->publisher->trust_tier,'status'=>$supplyChain->publisher->status] : null,
            ] : null,
            'canRescan' => $request->user()?->hasPermission('security.sentinel.scan') ?? false,
            'canDelete' => $request->user()?->hasPermission('security.quarantine.manage') ?? false,
        ]);
    }

    public function rescan(Request $request, QuarantinePackage $package): RedirectResponse
    {
        if (! is_file($package->path)) {
            return back()->with('error', 'The quarantined package file is no longer available.');
        }

        $scan = $this->recorder->scan($package, $request->user()?->id);
        $this->audit->record('sentinel.package.rescanned', $scan, ['package_id' => $package->id, 'decision' => $scan->decision, 'risk_score' => $scan->risk_score]);

        return redirect()->route('admin.security.sentinel.show', $scan)->with('success', 'Package rescanned with the current Sentinel engine.');
    }

    public function destroy(QuarantinePackage $package): RedirectResponse
    {
        $name = $package->original_name;
        $sha256 = $package->sha256;
        $this->quarantine->delete($package);
        $this->audit->record('sentinel.quarantine.deleted', $package, ['name' => $name, 'sha256' => $sha256]);

        return redirect()->route('admin.security.sentinel.index')->with('success', "Quarantined package [{$name}] was permanently removed.");
    }
}
