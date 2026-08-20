<?php

declare(strict_types=1);

namespace App\Nexora\Security\SupplyChain\Services;

use App\Models\QuarantinePackage;
use App\Models\SecurityScan;
use App\Models\SupplyChainArtifact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class SupplyChainAnalyzer
{
    public function __construct(
        private PackageContentDigest $contentDigest,
        private SbomService $sbom,
        private SignatureVerifier $signatures,
        private ProvenanceService $provenance,
        private PolicySandboxAdapter $sandbox,
    ) {}

    public function analyze(QuarantinePackage $package, SecurityScan $scan): SupplyChainArtifact
    {
        $path = (string) $package->path;
        $artifactSha = hash_file('sha256', $path);
        if (! is_string($artifactSha)) throw new \RuntimeException('Unable to hash quarantined artifact for supply-chain analysis.');
        $contentSha = $this->contentDigest->calculate($path);
        $signature = $this->signatures->verify($path, $contentSha);
        $signatureVerified = $signature['status'] === 'verified';
        $provenance = $this->provenance->inspect($path, $contentSha, $signatureVerified);
        $sbom = $this->sbom->inspect($path, $artifactSha);
        $manifest = (array) ($scan->manifest ?? []);
        $publisher = $signature['publisher'];
        $trustTier = $signatureVerified && $publisher ? (string) $publisher->trust_tier : 'untrusted';
        if (! in_array($trustTier, ['untrusted','verified','trusted','core'], true)) $trustTier = 'untrusted';

        return DB::transaction(function () use ($package,$scan,$artifactSha,$contentSha,$signature,$signatureVerified,$provenance,$sbom,$manifest,$publisher,$trustTier): SupplyChainArtifact {
            $artifact = SupplyChainArtifact::query()->firstOrNew(['scan_id'=>$scan->id]);
            if (! $artifact->exists) $artifact->id = (string) Str::uuid();
            $artifact->fill([
                    'quarantine_package_id'=>$package->id,
                    'package_identifier'=>is_string($manifest['id'] ?? null) ? $manifest['id'] : null,
                    'package_version'=>is_string($manifest['version'] ?? null) ? $manifest['version'] : null,
                    'artifact_sha256'=>$artifactSha,
                    'content_sha256'=>$contentSha,
                    'publisher_id'=>$publisher?->id,
                    'signature_status'=>$signature['status'],
                    'provenance_status'=>$provenance['status'],
                    'trust_tier'=>$trustTier,
                    'sandbox_profile'=>'deny-execution',
                    'sbom_format'=>$sbom['format'],
                    'sbom_version'=>$sbom['version'],
                    'sbom'=>$sbom['bom'],
                    'provenance'=>$provenance['payload'],
                    'verification_error'=>$signature['error'] ?? $provenance['error'],
                    'verified_at'=>$signatureVerified ? now() : null,
                ])->save();
            $artifact->components()->delete();
            foreach ($sbom['components'] as $component) {
                $artifact->components()->create([
                    'ecosystem'=>(string)($component['ecosystem']??'generic'),
                    'name'=>mb_substr((string)($component['name']??'unknown'),0,255),
                    'version'=>mb_substr((string)($component['version']??''),0,120) ?: null,
                    'scope'=>(string)($component['scope']??'runtime'),
                    'is_direct'=>(bool)($component['is_direct']??false),
                    'purl'=>isset($component['purl']) ? mb_substr((string)$component['purl'],0,700) : null,
                    'licenses'=>(array)($component['licenses']??[]),
                    'hashes'=>(array)($component['hashes']??[]),
                    'metadata'=>(array)($component['metadata']??[]),
                ]);
            }
            $artifact->attestations()->delete();
            if (is_array($signature['payload'])) {
                $artifact->attestations()->create([
                    'id'=>(string)Str::uuid(),'kind'=>'signature','subject_sha256'=>$contentSha,'issuer'=>$publisher?->key_id,
                    'verified'=>$signatureVerified,'payload'=>$signature['payload'],'verification_error'=>$signature['error'],'verified_at'=>$signatureVerified?now():null,
                ]);
            }
            if (is_array($provenance['payload'])) {
                $artifact->attestations()->create([
                    'id'=>(string)Str::uuid(),'kind'=>'provenance','predicate_type'=>(string)($provenance['payload']['build_type']??'nexora.build'),
                    'subject_sha256'=>$contentSha,'issuer'=>$publisher?->key_id,'verified'=>$provenance['status']==='verified','payload'=>$provenance['payload'],
                    'verification_error'=>$provenance['error'],'verified_at'=>$provenance['status']==='verified'?now():null,
                ]);
            }
            $evaluation = $this->sandbox->evaluate($artifact);
            $artifact->forceFill(['sandbox_profile'=>$evaluation['profile']])->save();
            return $artifact->fresh(['publisher','components','attestations']) ?? $artifact;
        });
    }
}
