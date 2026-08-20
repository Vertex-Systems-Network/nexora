<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Upgrade;

use App\Nexora\Installation\InstallationState;
use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;

final readonly class TrustedUpdateAdmission
{
    public function __construct(private InstallationState $installation,private RuntimeDeploymentIdentity $deployment) {}

    public function path(): string
    {
        return (string) config('nexora-update-trust.admission_path', base_path('storage/app/nexora/update-trust/admission.json'));
    }

    /** @return array{ok:bool,errors:list<string>,receipt:?array<string,mixed>,receipt_sha256:?string} */
    public function verify(): array
    {
        $errors=[];$path=$this->path();
        if(!is_file($path)) return ['ok'=>false,'errors'=>['Signed release admission receipt missing. Admit and stage the trusted release before creating an upgrade plan.'],'receipt'=>null,'receipt_sha256'=>null];
        try{$receipt=json_decode((string)file_get_contents($path),true,256,JSON_THROW_ON_ERROR);}catch(\Throwable $e){return ['ok'=>false,'errors'=>['Signed release admission receipt is invalid JSON: '.$e->getMessage()],'receipt'=>null,'receipt_sha256'=>null];}
        if(!is_array($receipt)) return ['ok'=>false,'errors'=>['Signed release admission receipt must be an object.'],'receipt'=>null,'receipt_sha256'=>null];
        if(($receipt['schema']??null)!==1||($receipt['status']??null)!=='admitted')$errors[]='Signed release admission schema/status is invalid.';
        $target=(string) config('nexora.version','');if(($receipt['target_version']??null)!==$target)$errors[]='Signed release admission target version does not match the deployed source tree.';
        $installed=$this->installation->metadata()??[];$source=(string)($installed['version']??'');if(($receipt['source_version']??null)!==$source)$errors[]='Signed release admission source version no longer matches installed metadata.';
        $expires=strtotime((string)($receipt['expires_at']??''));if($expires===false||$expires<time())$errors[]='Signed release admission expired; re-admit the release before upgrade.';
        $admitted=strtotime((string)($receipt['admitted_at']??''));$skew=(int)config('nexora-update-trust.clock_skew_seconds',300);if($admitted===false||$admitted>time()+$skew)$errors[]='Signed release admission timestamp is invalid.';
        $anchor=(string)config('nexora-update-trust.trusted_anchor_path',base_path('storage/app/nexora/update-trust/trusted-anchor.json'));$anchorHash=is_file($anchor)?(hash_file('sha256',$anchor)?:null):null;if(!is_string($anchorHash)||($receipt['recipient_trust_anchor_sha256']??null)!==$anchorHash)$errors[]='Recipient update trust anchor changed since release admission.';
        require_once base_path('scripts/lib/trusted-update.php');$lineage=\nexoraVerifyRecipientTrustLineage(base_path());if(!$lineage['ok'])foreach($lineage['errors'] as $lineageError)$errors[]='Recipient trust-anchor lineage: '.$lineageError;if(($receipt['trust_lineage_head_sha256']??null)!==($lineage['head_sha256']??null))$errors[]='Recipient trust-anchor lineage head changed since release admission.';if((int)($receipt['trust_lineage_depth']??0)!==(int)($lineage['depth']??0))$errors[]='Recipient trust-anchor lineage depth changed since release admission.';
        $kind=(string)($receipt['admission_kind']??'signed-release');
        if($kind==='certification-candidate'){
            if(!(bool)config('nexora-update-trust.allow_certification_candidate',false))$errors[]='Certification update candidate admission is disabled outside an explicit rehearsal.';
            $env=(string)config('app.env','production');if(!in_array($env,['local','testing','certification'],true))$errors[]='Certification update candidate requires local/testing/certification APP_ENV.';
            $connection=(string)config('database.default');$database=(string)config('database.connections.'.$connection.'.database','');$safe=false;foreach((array)config('nexora-update-trust.certification_safe_database_prefixes',[]) as $prefix)if(str_starts_with(strtolower($database),strtolower((string)$prefix)))$safe=true;if(!$safe)$errors[]='Certification update candidate requires an isolated nexora_test*/nexora_cert* database.';
        } elseif($kind!=='signed-release') $errors[]='Unknown trusted-update admission kind.';
        require_once base_path('scripts/lib/source-attestation.php');$att=\nexoraComputeSourceAttestation(base_path());if(($receipt['target_source_tree_sha256']??null)!==($att['tree_sha256']??null))$errors[]='Currently deployed source tree does not match the admitted signed release.';
        $currentDeployment=$this->deployment->current();if(isset($receipt['target_deployment_generation'])&&($receipt['target_deployment_generation']??null)!==($currentDeployment['generation']??null))$errors[]='Currently deployed runtime generation does not match the admitted release/certification candidate.';if(isset($receipt['target_frontend_manifest_sha256'])&&($receipt['target_frontend_manifest_sha256']??null)!==($currentDeployment['frontend_manifest_sha256']??null))$errors[]='Currently deployed frontend manifest does not match the admitted release/certification candidate.';
        if((bool)config('nexora-update-trust.require_monotonic_version',true)&&$source!==''&&$target!==''&&(version_compare($target,$source,'<')||(version_compare($target,$source,'==')&&!(bool)config('nexora-update-trust.allow_reinstall_same_version',false))))$errors[]="Non-monotonic update blocked: {$source} -> {$target}.";
        $receiptHash=hash_file('sha256',$path)?:null;return ['ok'=>$errors===[],'errors'=>array_values(array_unique($errors)),'receipt'=>$receipt,'receipt_sha256'=>$receiptHash];
    }

    public function clear(): void
    {
        $path=$this->path();if(is_file($path)&&!@unlink($path))throw new \RuntimeException('Unable to clear trusted update admission receipt after successful upgrade.');
    }
}
