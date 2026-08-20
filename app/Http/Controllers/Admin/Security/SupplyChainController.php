<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\SupplyChainArtifact;
use App\Models\SupplyChainComponent;
use App\Models\TrustedPublisher;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class SupplyChainController extends Controller
{
    public function index(Request $request): Response
    {
        $artifacts = SupplyChainArtifact::query()
            ->with(['publisher:id,name,key_id,trust_tier,status', 'scan:id,source_name,decision,risk_score,created_at'])
            ->withCount('components')
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(static fn (SupplyChainArtifact $artifact): array => [
                'id'=>$artifact->id,
                'package_identifier'=>$artifact->package_identifier,
                'package_version'=>$artifact->package_version,
                'artifact_sha256'=>$artifact->artifact_sha256,
                'content_sha256'=>$artifact->content_sha256,
                'signature_status'=>$artifact->signature_status,
                'provenance_status'=>$artifact->provenance_status,
                'trust_tier'=>$artifact->trust_tier,
                'sandbox_profile'=>$artifact->sandbox_profile,
                'sbom_format'=>$artifact->sbom_format,
                'sbom_version'=>$artifact->sbom_version,
                'components_count'=>$artifact->components_count,
                'verification_error'=>$artifact->verification_error,
                'verified_at'=>$artifact->verified_at?->toIso8601String(),
                'created_at'=>$artifact->created_at?->toIso8601String(),
                'publisher'=>$artifact->publisher ? ['name'=>$artifact->publisher->name,'key_id'=>$artifact->publisher->key_id,'trust_tier'=>$artifact->publisher->trust_tier,'status'=>$artifact->publisher->status] : null,
                'scan'=>$artifact->scan ? ['id'=>$artifact->scan->id,'source_name'=>$artifact->scan->source_name,'decision'=>$artifact->scan->decision,'risk_score'=>$artifact->scan->risk_score] : null,
            ]);

        $publishers = TrustedPublisher::query()->withCount('artifacts')->orderBy('name')->get()->map(static fn (TrustedPublisher $publisher): array => [
            'id'=>$publisher->id,'name'=>$publisher->name,'key_id'=>$publisher->key_id,'fingerprint_sha256'=>$publisher->fingerprint_sha256,
            'algorithm'=>$publisher->algorithm,'trust_tier'=>$publisher->trust_tier,'status'=>$publisher->status,'artifacts_count'=>$publisher->artifacts_count,
            'created_at'=>$publisher->created_at?->toIso8601String(),
        ])->values();

        return Inertia::render('Admin/Security/SupplyChain/Index', [
            'artifacts'=>$artifacts,
            'publishers'=>$publishers,
            'summary'=>[
                'artifacts'=>SupplyChainArtifact::query()->count(),
                'verified'=>SupplyChainArtifact::query()->where('signature_status','verified')->count(),
                'unsigned'=>SupplyChainArtifact::query()->where('signature_status','missing')->count(),
                'components'=>SupplyChainComponent::query()->count(),
                'trusted_publishers'=>TrustedPublisher::query()->where('status','active')->count(),
            ],
            'environment'=>[
                'sodium'=>extension_loaded('sodium') && function_exists('sodium_crypto_sign_verify_detached'),
                'signature_protocol'=>'Ed25519 over the binary SHA-256 content digest; nexora.signature.json is excluded from the deterministic content digest.',
            ],
            'canManagePublishers'=>$request->user()?->hasPermission('security.publishers.manage') ?? false,
        ]);
    }

    public function storePublisher(Request $request, AuditManager $audit): RedirectResponse
    {
        $data = $request->validate([
            'name'=>['required','string','max:180'],
            'key_id'=>['required','string','max:160','regex:/^[A-Za-z0-9._:-]+$/','unique:nx_trusted_publishers,key_id'],
            'public_key'=>['required','string','max:512'],
            'trust_tier'=>['required',Rule::in(['verified','trusted'])],
        ]);
        $decoded = base64_decode(trim($data['public_key']), true);
        if (! is_string($decoded) || strlen($decoded) !== 32) {
            return back()->withErrors(['public_key'=>'Provide the raw 32-byte Ed25519 public key encoded as Base64.']);
        }
        $publisher = TrustedPublisher::query()->create([
            'id'=>(string) Str::uuid(),
            'name'=>trim($data['name']),
            'key_id'=>trim($data['key_id']),
            'algorithm'=>'ed25519',
            'public_key'=>base64_encode($decoded),
            'fingerprint_sha256'=>hash('sha256',$decoded),
            'trust_tier'=>$data['trust_tier'],
            'status'=>'active',
            'metadata'=>[],
            'created_by'=>$request->user()?->id,
        ]);
        $audit->record('sentinel.publisher.trusted',$publisher,['key_id'=>$publisher->key_id,'fingerprint'=>$publisher->fingerprint_sha256,'trust_tier'=>$publisher->trust_tier]);
        return back()->with('success','Publisher verification key added. Private signing keys are never stored by Nexora.');
    }

    public function revokePublisher(TrustedPublisher $publisher, AuditManager $audit): RedirectResponse
    {
        if ($publisher->status === 'revoked') return back()->with('warning','This publisher key is already revoked.');
        $publisher->forceFill(['status'=>'revoked'])->save();
        $audit->record('sentinel.publisher.revoked',$publisher,['key_id'=>$publisher->key_id,'fingerprint'=>$publisher->fingerprint_sha256]);
        return back()->with('success','Publisher key revoked. Existing artifact verification history is preserved.');
    }
}
