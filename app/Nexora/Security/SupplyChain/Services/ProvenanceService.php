<?php

declare(strict_types=1);

namespace App\Nexora\Security\SupplyChain\Services;

final readonly class ProvenanceService
{
    public function __construct(private PackageJsonReader $json) {}

    /** @return array{status:string,payload:?array<string,mixed>,error:?string} */
    public function inspect(string $zipPath, string $contentSha256, bool $signatureVerified): array
    {
        $payload = $this->json->read($zipPath, 'nexora.provenance.json', 524_288);
        if (! is_array($payload)) return ['status'=>'missing','payload'=>null,'error'=>null];
        $subject = strtolower((string) ($payload['subject_sha256'] ?? ''));
        if (preg_match('/^[a-f0-9]{64}$/', $subject) !== 1 || ! hash_equals($contentSha256, $subject)) {
            return ['status'=>'invalid','payload'=>$this->sanitize($payload),'error'=>'Provenance subject_sha256 does not match the current package content digest.'];
        }
        return [
            'status'=>$signatureVerified ? 'verified' : 'declared',
            'payload'=>$this->sanitize($payload),
            'error'=>$signatureVerified ? null : 'Provenance matches the artifact but is not backed by a verified publisher signature.',
        ];
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function sanitize(array $payload): array
    {
        $allowed = ['version','subject_sha256','builder','build_type','source_repository','source_commit','build_started_at','build_finished_at','materials'];
        return array_intersect_key($payload, array_flip($allowed));
    }
}
