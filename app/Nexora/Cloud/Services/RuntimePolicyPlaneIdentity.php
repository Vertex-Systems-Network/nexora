<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Services;

final class RuntimePolicyPlaneIdentity
{
    public const RUNTIME_SOURCE_GENERATION = 'n1-v5.29';

    private ?array $memo=null;

    /** @return array<string,mixed> */
    public function current(bool $deep=false): array
    {
        if(!$deep&&is_array($this->memo)) return $this->memo;
        $materials=$this->materials();$checks=$this->checks($materials);$fingerprint=$this->hash($materials);
        $payload=['schema'=>1,'status'=>in_array(false,$checks,true)?'fail':'pass','fingerprint'=>$fingerprint,'materials'=>$materials,'checks'=>$checks];
        if($deep){$payload['deep']=['status'=>$payload['status'],'checks'=>$checks,'deep_sha256'=>$this->hash(['materials'=>$materials,'checks'=>$checks])];}
        if(!$deep)$this->memo=$payload;
        return $payload;
    }

    public function fingerprintValue(): string { return (string)$this->current(false)['fingerprint']; }


    /** @return array<string,mixed> */
    public function installationAttestation(): array
    {
        $strict = $this->current(true);
        $materials = (array) ($strict['materials'] ?? []);
        $runtime = (array) ($materials['runtime'] ?? []);
        $transfers = (array) ($materials['transfers'] ?? []);
        $dependencies = (array) ($materials['dependencies'] ?? []);
        $concurrency = (array) ($materials['concurrency'] ?? []);
        $policy = (array) ($materials['policy_plane'] ?? []);

        $checks = [
            'policy_plane_enabled' => (bool) ($policy['require_exact_policy_plane'] ?? false),
            'queue_schema_current' => (int) ($policy['queue_payload_schema'] ?? 0) >= 13,
            'dependency_lock_policy_fail_closed' => (bool) ($dependencies['forbid_unlocked_certification'] ?? false)
                && (bool) ($dependencies['forbid_install_mutating_lockfiles'] ?? false),
            'external_effect_semantics_explicit' => (string) ($concurrency['external_effect_semantics'] ?? '') === 'at-least-once',
            'media_upload_within_http_limit' => (int) ($transfers['media_max_upload_bytes'] ?? PHP_INT_MAX)
                <= (int) ($runtime['http_max_body_bytes'] ?? 0),
        ];

        $blocking = [];
        foreach ($checks as $name => $ok) {
            if ($ok !== true) {
                $blocking[] = match ($name) {
                    'policy_plane_enabled' => 'The Nexora runtime policy plane is disabled.',
                    'queue_schema_current' => 'The configured queue payload schema is below Nexora schema 13.',
                    'dependency_lock_policy_fail_closed' => 'Dependency lock policy must forbid unlocked certification and install-time lock mutation.',
                    'external_effect_semantics_explicit' => 'External-effect concurrency semantics must remain explicitly at-least-once.',
                    'media_upload_within_http_limit' => 'Configured media upload size exceeds the Nexora application HTTP body limit.',
                    default => "Runtime policy installation check failed [{$name}].",
                };
            }
        }

        $warnings = [];
        if (($strict['status'] ?? 'fail') !== 'pass') {
            $failedStrict = array_keys(array_filter(
                (array) ($strict['checks'] ?? []),
                static fn (mixed $ok): bool => $ok !== true,
            ));
            $warnings[] = 'Strict runtime-policy certification is not PASS yet. Pending strict checks: '
                .($failedStrict === [] ? 'unknown' : implode(', ', $failedStrict)).'.';
        }

        return [
            ...$strict,
            'installation_status' => $blocking === [] ? 'pass' : 'fail',
            'installation_checks' => $checks,
            'installation_blocking_reasons' => $blocking,
            'installation_warnings' => $warnings,
        ];
    }

    /** @return array<string,mixed> */
    private function materials(): array
    {
        return [
            'schema'=>1,
            'concurrency'=>[
                'transaction_attempts'=>(int)config('nexora-concurrency.transaction_attempts',3),
                'workflow_claim_ttl_seconds'=>(int)config('nexora-concurrency.workflow_claim_ttl_seconds',240),
                'webhook_claim_ttl_seconds'=>(int)config('nexora-concurrency.webhook_claim_ttl_seconds',90),
                'newsletter_claim_ttl_seconds'=>(int)config('nexora-concurrency.newsletter_claim_ttl_seconds',180),
                'external_effect_semantics'=>(string)config('nexora-concurrency.external_effect_semantics','at-least-once'),
                'supported_drivers'=>$this->sortedStrings((array)config('nexora-concurrency.supported_drivers',[])),
            ],
            'transfers'=>[
                'stream_chunk_bytes'=>(int)config('nexora-transfers.stream_chunk_bytes',1_048_576),
                'minimum_free_bytes'=>(int)config('nexora-transfers.minimum_free_bytes',67_108_864),
                'media_max_upload_bytes'=>(int)config('nexora-transfers.media.max_upload_bytes',52_428_800),
                'media_variant_decode_max_bytes'=>(int)config('nexora-transfers.media.variant_decode_max_bytes',20_971_520),
                'marketplace_max_download_bytes'=>(int)config('nexora-transfers.marketplace.max_download_bytes',52_428_800),
                'theme_archive'=>(array)config('nexora-transfers.archives.theme',[]),
                'extension_archive'=>(array)config('nexora-transfers.archives.extension',[]),
                'backup_max_bytes'=>(int)config('nexora-transfers.backup.max_bytes',53_687_091_200),
                'backup_minimum_free_bytes'=>(int)config('nexora-transfers.backup.minimum_free_bytes',268_435_456),
            ],
            'runtime'=>[
                'http_max_body_bytes'=>(int)config('nexora-runtime.http.max_body_bytes',67_108_864),
                'trusted_proxies'=>$this->sortedStrings((array)config('nexora-runtime.http.trusted_proxies',[])),
                'allow_insecure_http'=>(bool)config('nexora-environment.allow_insecure_http',false),
                'php'=>(array)config('nexora-runtime.php',[]),
                'queue'=>(array)config('nexora-runtime.queue',[]),
                'deployment'=>$this->pick((array)config('nexora-runtime.deployment',[]),['cache_generation_fencing','session_schema_enforced','session_schema','json_client_generation_fence','environment_fingerprint_enforced','key_rotation_require_maintenance','key_rotation_require_previous_key','key_rotation_receipt_ttl_minutes','key_rotation_cluster_convergence_required']),
            ],
            'upgrade'=>$this->pick((array)config('nexora-upgrade',[]),['require_backup','require_restore_readiness','block_preexisting_maintenance','backup_fresh_minutes','restore_plan_fresh_minutes','plan_ttl_minutes','require_migration_ledger','require_cluster_quiescence','block_destructive_pending_migrations','runtime_admission_barrier_required','queue_payload_schema','queue_payload_require_metadata','queue_payload_require_exact_version','queue_payload_require_exact_generation','queue_payload_require_exact_environment','client_generation_fence_required']),
            'update_trust'=>$this->pick((array)config('nexora-update-trust',[]),['admission_ttl_minutes','quarantine_ttl_hours','require_signed_release','require_monotonic_version','require_exact_source_after_deploy','allow_reinstall_same_version','clock_skew_seconds','allow_certification_candidate','certification_candidate_ttl_minutes','certification_safe_database_prefixes']),
            'release_trust'=>[
                'signature_required'=>(bool)config('nexora-release-trust.signature_required',true),
                'signature_algorithm'=>(string)config('nexora-release-trust.signature_algorithm','sha256WithRSA'),
                'rsa_bits'=>(int)config('nexora-release-trust.rsa_bits',3072),
                'external_identity_anchor_required'=>(bool)config('nexora-release-trust.external_identity_anchor_required',true),
                'archive'=>(array)config('nexora-release-trust.archive',[]),
            ],
            'supply_chain'=>[
                'sbom'=>(array)config('nexora-supply-chain.sbom',[]),
                'production_dependencies'=>$this->pick((array)config('nexora-supply-chain.production_dependencies',[]),['composer_no_dev_required','composer_no_scripts_required']),
                'provenance'=>(array)config('nexora-supply-chain.provenance',[]),
                'content_manifest'=>(array)config('nexora-supply-chain.content_manifest',[]),
                'external_anchor_required'=>(bool)config('nexora-supply-chain.offline_identity.external_anchor_required',true),
            ],
            'dependencies'=>[
                'php'=>(array)config('nexora-dependencies.php',[]),
                'composer'=>(array)config('nexora-dependencies.composer',[]),
                'node'=>(array)config('nexora-dependencies.node',[]),
                'npm'=>(array)config('nexora-dependencies.npm',[]),
                'lockfiles'=>(array)config('nexora-dependencies.lockfiles',[]),
                'deterministic_install'=>(array)config('nexora-dependencies.deterministic_install',[]),
                'forbid_unlocked_certification'=>(bool)config('nexora-dependencies.forbid_unlocked_certification',true),
                'forbid_install_mutating_lockfiles'=>(bool)config('nexora-dependencies.forbid_install_mutating_lockfiles',true),
            ],
            'ha'=>[
                'required_nodes'=>(int)config('nexora-ha.required_nodes',2),
                'fresh_node_seconds'=>(int)config('nexora-ha.fresh_node_seconds',180),
                'shared_cache_stores'=>$this->sortedStrings((array)config('nexora-ha.shared_cache_stores',[])),
                'shared_session_drivers'=>$this->sortedStrings((array)config('nexora-ha.shared_session_drivers',[])),
                'async_queue_connections'=>$this->sortedStrings((array)config('nexora-ha.async_queue_connections',[])),
                'shared_storage_drivers'=>$this->sortedStrings((array)config('nexora-ha.shared_storage_drivers',[])),
            ],
            'process_runtime'=>[
                'require_exact_process_policy'=>(bool)config('nexora-process-runtime.require_exact_process_policy',true),
                'lease_seconds'=>(int)config('nexora-process-runtime.lease_seconds',180),
                'heartbeat_throttle_seconds'=>(int)config('nexora-process-runtime.heartbeat_throttle_seconds',30),
                'minimum_web_nodes'=>(int)config('nexora-process-runtime.minimum_web_nodes',2),
                'minimum_queue_nodes'=>(int)config('nexora-process-runtime.minimum_queue_nodes',2),
                'minimum_scheduler_nodes'=>(int)config('nexora-process-runtime.minimum_scheduler_nodes',1),
                'require_web_for_ha'=>(bool)config('nexora-process-runtime.require_web_for_ha',true),
                'require_queue_for_async'=>(bool)config('nexora-process-runtime.require_queue_for_async',true),
                'require_scheduler_for_ha'=>(bool)config('nexora-process-runtime.require_scheduler_for_ha',true),
                'reject_indefinite_queue_blocking_for_ha'=>(bool)config('nexora-process-runtime.reject_indefinite_queue_blocking_for_ha',true),
                'queue_max_block_seconds'=>(int)config('nexora-process-runtime.queue_max_block_seconds',30),
            ],
            'policy_plane'=>[
                'require_exact_policy_plane'=>(bool)config('nexora-policy-runtime.require_exact_policy_plane',true),
                'production_fail_closed'=>(bool)config('nexora-policy-runtime.production_fail_closed',true),
                'queue_payload_schema'=>max(13,(int)config('nexora-policy-runtime.queue_payload_schema',13)),
            ],
        ];
    }

    /** @param array<string,mixed> $m @return array<string,bool> */
    private function checks(array $m): array
    {
        $production=app()->environment('production');$failClosed=(bool)($m['policy_plane']['production_fail_closed']??true);
        $deployment=(array)$m['runtime']['deployment'];$processRuntime=(array)($m['process_runtime']??[]);$upgrade=(array)$m['upgrade'];$update=(array)$m['update_trust'];$release=(array)$m['release_trust'];$supply=(array)$m['supply_chain'];$deps=(array)$m['dependencies'];
        return [
            'policy_plane_enabled'=>(bool)($m['policy_plane']['require_exact_policy_plane']??false),
            'queue_schema_current'=>(int)($m['policy_plane']['queue_payload_schema']??0)>=13,
            'http_transport_fail_closed'=>!$production||!$failClosed||!(bool)($m['runtime']['allow_insecure_http']??true),
            'deployment_fences_fail_closed'=>!$production||!$failClosed||collect(['cache_generation_fencing','session_schema_enforced','json_client_generation_fence','environment_fingerprint_enforced','key_rotation_require_maintenance','key_rotation_require_previous_key','key_rotation_cluster_convergence_required'])->every(fn(string $k):bool=>(bool)($deployment[$k]??false)),
            'upgrade_safety_fail_closed'=>!$production||!$failClosed||collect(['require_backup','require_restore_readiness','require_migration_ledger','require_cluster_quiescence','block_destructive_pending_migrations','runtime_admission_barrier_required','queue_payload_require_metadata','queue_payload_require_exact_version','queue_payload_require_exact_generation','queue_payload_require_exact_environment','client_generation_fence_required'])->every(fn(string $k):bool=>(bool)($upgrade[$k]??false)),
            'update_trust_fail_closed'=>!$production||!$failClosed||((bool)($update['require_signed_release']??false)&&(bool)($update['require_monotonic_version']??false)&&(bool)($update['require_exact_source_after_deploy']??false)&&!(bool)($update['allow_reinstall_same_version']??true)&&!(bool)($update['allow_certification_candidate']??true)),
            'release_trust_fail_closed'=>!$production||!$failClosed||((bool)($release['signature_required']??false)&&(bool)($release['external_identity_anchor_required']??false)&&(bool)($release['archive']['reject_case_collisions']??false)&&(bool)($release['archive']['reject_symlinks']??false)&&(bool)($release['archive']['reject_unsafe_paths']??false)),
            'supply_chain_fail_closed'=>!$production||!$failClosed||((bool)($supply['sbom']['required']??false)&&(bool)($supply['production_dependencies']['composer_no_dev_required']??false)&&(bool)($supply['production_dependencies']['composer_no_scripts_required']??false)&&(bool)($supply['provenance']['required']??false)&&(bool)($supply['content_manifest']['required']??false)&&(bool)($supply['external_anchor_required']??false)),
            'dependency_lock_policy_fail_closed'=>(bool)($deps['forbid_unlocked_certification']??false)&&(bool)($deps['forbid_install_mutating_lockfiles']??false),
            'external_effect_semantics_explicit'=>(string)($m['concurrency']['external_effect_semantics']??'')==='at-least-once',
            'media_upload_within_http_limit'=>(int)($m['transfers']['media_max_upload_bytes']??PHP_INT_MAX)<=(int)($m['runtime']['http_max_body_bytes']??0),
            'ha_minimum_nodes'=>(int)($m['ha']['required_nodes']??0)>=2,
            'process_role_policy_fail_closed'=>(bool)($processRuntime['require_exact_process_policy']??false)&&(int)($processRuntime['lease_seconds']??0)>=(int)($processRuntime['heartbeat_throttle_seconds']??0)*2&&(int)($processRuntime['minimum_web_nodes']??0)>=1&&(int)($processRuntime['minimum_queue_nodes']??0)>=1&&(int)($processRuntime['minimum_scheduler_nodes']??0)>=1,
        ];
    }

    /** @param array<int|string,mixed> $values @return list<string> */
    private function sortedStrings(array $values): array { $out=array_values(array_unique(array_map(static fn(mixed $v):string=>trim((string)$v),$values)));sort($out,SORT_STRING);return $out; }

    /** @param array<string,mixed> $source @param list<string> $keys @return array<string,mixed> */
    private function pick(array $source,array $keys): array { $out=[];foreach($keys as $k)if(array_key_exists($k,$source))$out[$k]=$source[$k];return $out; }
    /** @param array<string,mixed> $payload */
    private function hash(array $payload): string { return hash('sha256',json_encode($this->canonicalize($payload),JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)); }
    private function canonicalize(mixed $value): mixed { if(!is_array($value))return $value;if(array_is_list($value)){foreach($value as &$v)$v=$this->canonicalize($v);unset($v);return $value;}ksort($value,SORT_STRING);foreach($value as $k=>$v)$value[$k]=$this->canonicalize($v);return $value; }
}
