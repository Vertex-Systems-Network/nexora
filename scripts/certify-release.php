<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require $root.'/bootstrap/nexora-runtime-bootstrap.php';
require_once $root.'/scripts/lib/source-attestation.php';

$sourceOnly = in_array('--source-only', $argv, true);
$skipPackage = in_array('--no-package', $argv, true);
$keepGoing = in_array('--keep-going', $argv, true);
$finalEvidenceRequired = (string) getenv('NEXORA_CERT_FINAL_EVIDENCE') === '1';
$reportDir = $root.'/storage/app/nexora/certification';
if (! is_dir($reportDir) && ! mkdir($reportDir, 0775, true) && ! is_dir($reportDir)) {
    fwrite(STDERR, "[Nexora RC] Unable to create certification report directory.\n");
    exit(1);
}

$platform = require $root.'/config/nexora.php';
$version = (string) ($platform['version'] ?? 'unknown');
$started = microtime(true);
$sourceAttestationInitial = nexoraComputeSourceAttestation($root);
$steps = [];
$failed = false;

$certDbConnection = strtolower((string) (getenv('NEXORA_CERT_DB_CONNECTION') ?: 'mysql'));
$certDbDatabase = (string) (getenv('NEXORA_CERT_DB_DATABASE') ?: ($certDbConnection === 'sqlite'
    ? $root.'/storage/app/nexora/certification/sqlite.sqlite'
    : 'nexora_certification'));

if ($certDbConnection !== 'sqlite' && preg_match('/^nexora[_-](?:test|testing|cert|certification)[A-Za-z0-9_-]*$/i', $certDbDatabase) !== 1) {
    fwrite(STDERR, "[Nexora RC] Refusing destructive certification against unsafe database [{$certDbDatabase}].\n");
    exit(1);
}
if ($certDbConnection === 'sqlite') {
    $safePrefix = str_replace('\\','/',$root.'/storage/app/nexora/certification/');
    if (! str_starts_with(str_replace('\\','/',$certDbDatabase), $safePrefix)) {
        fwrite(STDERR, "[Nexora RC] SQLite certification database must live under storage/app/nexora/certification/.\n");
        exit(1);
    }
}

$env = NexoraBootstrapProcessEnvironment::build($root, [
    'APP_ENV' => 'testing',
    'APP_DEBUG' => 'false',
    'NEXORA_INSTALLER_BYPASS' => 'true',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'DB_CONNECTION' => $certDbConnection,
    'DB_HOST' => (string) (getenv('NEXORA_CERT_DB_HOST') ?: getenv('DB_HOST') ?: '127.0.0.1'),
    'DB_PORT' => (string) (getenv('NEXORA_CERT_DB_PORT') ?: getenv('DB_PORT') ?: match ($certDbConnection) { 'pgsql'=>'5432','sqlsrv'=>'1433',default=>'3306' }),
    'DB_DATABASE' => $certDbDatabase,
    'DB_USERNAME' => (string) (getenv('NEXORA_CERT_DB_USERNAME') ?: getenv('DB_USERNAME') ?: match ($certDbConnection) { 'pgsql'=>'postgres','sqlsrv'=>'sa',default=>'root' }),
    'DB_PASSWORD' => (($p = getenv('NEXORA_CERT_DB_PASSWORD')) !== false ? (string) $p : (($p = getenv('DB_PASSWORD')) !== false ? (string) $p : ($certDbConnection === 'mysql' ? 'root' : ''))),
    'NEXORA_CERT_EXPECT_DB_CONNECTION' => $certDbConnection,
    'NEXORA_CERT_EXPECT_DB_DATABASE' => $certDbDatabase,
]);

$quote = static fn (string $part): string => escapeshellarg($part);
$php = $quote(PHP_BINARY);
$command = static function (array $parts) use ($quote): string {
    return implode(' ', array_map(static fn ($part): string => $quote((string) $part), $parts));
};

$writeReport = static function (string $status, array $steps, ?string $message = null) use ($root,$reportDir,$version,$started,$certDbConnection,$certDbDatabase,$finalEvidenceRequired,$sourceAttestationInitial): array {
    $completed = gmdate(DATE_ATOM);
    $payload = [
        'schema'=>1,
        'status'=>$status,
        'platform_version'=>$version,
        'started_at'=>gmdate(DATE_ATOM, (int) $started),
        'completed_at'=>$completed,
        'duration_seconds'=>round(microtime(true)-$started,3),
        'php_version'=>PHP_VERSION,
        'os_family'=>PHP_OS_FAMILY,
        'database'=>['connection'=>$certDbConnection,'database'=>basename(str_replace('\\','/',$certDbDatabase))],
        'source_tree_sha256'=>$sourceAttestationInitial['tree_sha256'],
        'source_file_count'=>$sourceAttestationInitial['file_count'],
        'steps'=>$steps,
        'message'=>$message,
        'manual_gates'=>[
            'browser_zero_install'=>$finalEvidenceRequired ? 'required by NEXORA_CERT_FINAL_EVIDENCE=1' : 'pending operator evidence',
            'existing_install_upgrade_rehearsal'=>$finalEvidenceRequired ? 'required by NEXORA_CERT_FINAL_EVIDENCE=1' : 'pending operator evidence',
            'wcag_2_2_aa'=>'automated source/component baseline + pending manual assistive-technology evidence',
            'responsive_rtl'=>'source contracts + pending observed browser matrix evidence',
            'backup_restore_rehearsal'=>$finalEvidenceRequired ? 'required by NEXORA_CERT_FINAL_EVIDENCE=1' : 'pending operator evidence',
            'multi_node_ha'=>$finalEvidenceRequired ? 'required by NEXORA_CERT_FINAL_EVIDENCE=1' : 'pending distributed-environment evidence',
            'http_performance'=>'automated when NEXORA_CERT_BASE_URL is configured; otherwise pending target-server evidence',
        ],
    ];
    $json=json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
    file_put_contents($reportDir.'/latest.json',$json);
    file_put_contents($reportDir.'/nexora-'.$version.'-'.gmdate('Ymd-His').'.json',$json);
    $md="# Nexora {$version} automated release certification\n\n";
    $md.="Status: **{$status}**  \nCompleted: {$completed}  \nDuration: {$payload['duration_seconds']}s  \nDatabase: {$certDbConnection} / ".basename(str_replace('\\','/',$certDbDatabase))."\n\n";
    $md.="| Gate | Status | Seconds |\n|---|---:|---:|\n";
    foreach($steps as $step)$md.='| '.str_replace('|','\\|',(string)$step['label']).' | '.strtoupper((string)$step['status']).' | '.number_format((float)$step['duration_seconds'],2).' |'."\n";
    if($message)$md.="\n{$message}\n";
    $md.="\n## Manual/operator evidence still required before stable N2.0\n\n- Browser zero-install + interrupted-recovery rehearsal\n- Existing-install upgrade rehearsal\n- WCAG 2.2 AA browser/manual audit\n- Responsive + RTL browser matrix\n- Strict MySQL/MariaDB/PostgreSQL/SQLite/SQL Server matrix\n- Backup/restore rehearsal\n- Multi-node HA rehearsal\n";
    file_put_contents($reportDir.'/latest.md',$md);
    return $payload;
};

$run = static function (string $id, string $label, string $cmd, bool $required = true) use (&$steps,&$failed,$env,$root,$keepGoing,$writeReport): bool {
    fwrite(STDOUT, "\n[Nexora RC] {$label}\n> {$cmd}\n");
    $startedStep=microtime(true);
    $descriptor=[1=>['pipe','w'],2=>['pipe','w']];
    $process=proc_open($cmd,$descriptor,$pipes,$root,$env,['bypass_shell'=>false]);
    if(!is_resource($process)){
        $exit=127;$stdout='';$stderr='Unable to start process.';
    } else {
        $stdout='';$stderr='';
        foreach([1,2] as $index) stream_set_blocking($pipes[$index], false);
        while(true){
            $status=proc_get_status($process);
            foreach([1,2] as $index){$chunk=stream_get_contents($pipes[$index]);if($chunk!==false&&$chunk!==''){if($index===1){$stdout.=$chunk;fwrite(STDOUT,$chunk);}else{$stderr.=$chunk;fwrite(STDERR,$chunk);}}}
            if(!$status['running'])break;
            usleep(50000);
        }
        foreach([1,2] as $index){$chunk=stream_get_contents($pipes[$index]);if($chunk!==false&&$chunk!==''){if($index===1){$stdout.=$chunk;fwrite(STDOUT,$chunk);}else{$stderr.=$chunk;fwrite(STDERR,$chunk);} } fclose($pipes[$index]);}
        $exit=proc_close($process);
        // Some platforms report -1 from proc_close after proc_get_status consumed the exit code. Preserve the status exit code when available.
        if($exit===-1 && isset($status['exitcode']) && $status['exitcode']>=0)$exit=(int)$status['exitcode'];
    }
    $ok=$exit===0;
    $steps[]=['id'=>$id,'label'=>$label,'status'=>$ok?'pass':($required?'fail':'skip'),'required'=>$required,'exit_code'=>$exit,'duration_seconds'=>round(microtime(true)-$startedStep,3),'stdout_tail'=>substr($stdout,-4000),'stderr_tail'=>substr($stderr,-4000)];
    if(!$ok&&$required){$failed=true;$writeReport('certification-fail',$steps,"Required gate failed: {$label}");if(!$keepGoing)throw new RuntimeException("Required gate failed: {$label}");}
    return $ok;
};

try {
    $run('preflight','RC source/runtime preflight',$php.' '.$quote($root.'/scripts/certification-preflight.php').($sourceOnly?' --source-only':''));
    $run('module-graph','Core module dependency graph',$php.' '.$quote($root.'/scripts/module-graph-verify.php'));
    $run('laravel-runtime-contract','Laravel middleware/route/scheduler runtime contracts',$php.' '.$quote($root.'/scripts/laravel-runtime-contract-verify.php'));
    $run('database-contract','Database migration/seeder/tenancy source contracts',$php.' '.$quote($root.'/scripts/database-contract-verify.php'));
    $run('zero-install-contract','Zero-install/deployment/recovery source contracts',$php.' '.$quote($root.'/scripts/zero-install-contract-verify.php'));
    $run('browser-ux-contract','Browser/UX/accessibility/RTL source contracts',$php.' '.$quote($root.'/scripts/browser-ux-contract-verify.php'));
    $run('inertia-frontend-contract','Inertia 3 form/router/frontend type contracts',$php.' '.$quote($root.'/scripts/inertia-frontend-contract-verify.php'));
    $run('target-runtime-contract','Fail-fast target runtime closure runner contracts',$php.' '.$quote($root.'/scripts/target-runtime-contract-verify.php'));
    $run('target-resume-contract','Target bootstrap/resume/evidence contracts',$php.' '.$quote($root.'/scripts/target-resume-contract-verify.php'));
    $run('target-intake-contract','Laragon prerequisite/lockfile intake contracts',$php.' '.$quote($root.'/scripts/target-intake-contract-verify.php'));
$run('target-remediation-contract','Laragon prerequisite remediation contracts',$php.' '.$quote($root.'/scripts/target-remediation-contract-verify.php'));
    $run('n1-c1-contract','N1.0-C1 target environment/dependency chunk contracts',$php.' '.$quote($root.'/scripts/n1-c1-contract-verify.php'));
    $run('n1-c2-contract','N1.0-C2 Laravel runtime/core database chunk contracts',$php.' '.$quote($root.'/scripts/n1-c2-contract-verify.php'));
    $run('n1-c3-contract','N1.0-C3 strict five-database matrix chunk contracts',$php.' '.$quote($root.'/scripts/n1-c3-contract-verify.php'));
    $run('n1-c4-contract','N1.0-C4 install/upgrade/backup recovery chunk contracts',$php.' '.$quote($root.'/scripts/n1-c4-contract-verify.php'));
    $run('n1-c5-contract','N1.0-C5 browser/accessibility/RTL/performance chunk contracts',$php.' '.$quote($root.'/scripts/n1-c5-contract-verify.php'));
    $run('n1-c6-contract','N1.0-C6 multi-node HA/final release chunk contracts',$php.' '.$quote($root.'/scripts/n1-c6-contract-verify.php'));
    $run('n1-target-execution-contract','N1.0 target execution pack contracts',$php.' '.$quote($root.'/scripts/n1-target-execution-contract-verify.php'));
    $run('n1-target-maximum-closure-contract','N1.0 target maximum closure contracts',$php.' '.$quote($root.'/scripts/n1-target-maximum-closure-contract-verify.php'));
    $run('n1-target-session-release-seal-contract','N1.0 certification session + final release seal contracts',$php.' '.$quote($root.'/scripts/n1-target-session-release-contract-verify.php'));
    $run('n1-target-release-trust-contract','N1.0 toolchain/signing/offline release trust contracts',$php.' '.$quote($root.'/scripts/n1-target-release-trust-contract-verify.php'));
    $run('n1-target-supply-chain-contract','N1.0 signer identity/SBOM/production runtime/provenance contracts',$php.' '.$quote($root.'/scripts/n1-target-supply-chain-contract-verify.php'));
    $run('n1-target-update-trust-contract','N1.0 signed-update admission/anti-rollback/trust-anchor contracts',$php.' '.$quote($root.'/scripts/n1-target-update-trust-contract-verify.php'));
    $run('target-evidence-contract','Unified target evidence intake/closure dashboard contracts',$php.' '.$quote($root.'/scripts/target-evidence-contract-verify.php'));
    $run('target-orchestrator-contract','One-command target certification orchestrator contracts',$php.' '.$quote($root.'/scripts/target-orchestrator-contract-verify.php'));
    $run('performance-contract','Performance/cache/production packaging source contracts',$php.' '.$quote($root.'/scripts/performance-contract-verify.php'));
    $run('ha-final-contract','Backup/restore/HA/final evidence source contracts',$php.' '.$quote($root.'/scripts/ha-final-contract-verify.php'));
    $run('final-closure-contract','Final target/closure harness source contracts',$php.' '.$quote($root.'/scripts/final-closure-contract-verify.php'));
    $run('target-diagnostics-contract','Target diagnostics/evidence capture source contracts',$php.' '.$quote($root.'/scripts/target-diagnostics-contract-verify.php'));
    $run('upgrade-contract','Existing-install upgrade/rollback safety contracts',$php.' '.$quote($root.'/scripts/upgrade-contract-verify.php'));
    $run('distributed-upgrade-contract','Distributed upgrade lease / node drain / migration-ledger contracts',$php.' '.$quote($root.'/scripts/n1-target-distributed-upgrade-contract-verify.php'));
    $run('runtime-quiescence-contract','Runtime quiescence / mixed-version fence contracts',$php.' '.$quote($root.'/scripts/n1-target-runtime-quiescence-contract-verify.php'));
    $run('cutover-barrier-contract','Atomic cutover barrier / exact queue payload / frontend v3 regression contracts',$php.' '.$quote($root.'/scripts/n1-target-cutover-barrier-contract-verify.php'));
    $run('deployment-generation-contract','Signed deployment generation / stale client / cache-session fencing contracts',$php.' '.$quote($root.'/scripts/n1-target-deployment-generation-contract-verify.php'));
    $run('runtime-environment-contract','Runtime environment / APP_KEY continuity / schema-4+ queue contracts',$php.' '.$quote($root.'/scripts/n1-target-runtime-environment-contract-verify.php'));
    $run('runtime-activation-contract','Runtime activation epoch / framework-cache / schema-6-compatible queue contracts',$php.' '.$quote($root.'/scripts/n1-target-runtime-activation-contract-verify.php'));
    $run('runtime-engine-contract','Runtime PHP engine / extension / PDO / schema-6 queue contracts',$php.' '.$quote($root.'/scripts/n1-target-runtime-engine-contract-verify.php'));
    $run('database-data-plane-contract','Database server/session/schema / forward-compatible queue contracts',$php.' '.$quote($root.'/scripts/n1-target-database-data-plane-contract-verify.php'));
    $run('storage-data-plane-contract','Persistent media/object/backup storage / forward-compatible queue contracts',$php.' '.$quote($root.'/scripts/n1-target-storage-data-plane-contract-verify.php'));
    $run('service-data-plane-contract','Cache/session/queue/mail/TLS/proxy services + approved outbound network / forward-compatible queue contracts',$php.' '.$quote($root.'/scripts/n1-target-service-data-plane-contract-verify.php'));
    $run('host-clock-contract','Host/platform/timezone/locale + DB-clock anchored lease / forward-compatible queue contracts',$php.' '.$quote($root.'/scripts/n1-target-host-clock-contract-verify.php'));
    $run('resource-envelope-contract','Runtime resource/capacity envelope + live upgrade/backup admission / forward-compatible queue contracts (current schema 13)',$php.' '.$quote($root.'/scripts/n1-target-resource-envelope-contract-verify.php'));
    $run('policy-plane-contract','Effective runtime policy-plane convergence / production fail-closed / schema-13 queue contracts',$php.' '.$quote($root.'/scripts/n1-target-policy-plane-contract-verify.php'));
    $run('process-plane-contract','Web/queue/scheduler process-role liveness / HA quorum / schema-13 process-policy contracts',$php.' '.$quote($root.'/scripts/n1-target-process-plane-contract-verify.php'));
    $run('framework-dependency-contract','Laravel 13.24+ reviewed dependency transition / deployment reconciliation / human-readable critical code contracts',$php.' '.$quote($root.'/scripts/n1-target-framework-dependency-contract-verify.php'));
    $run('tenant-seed-typescript-contract','Tenant seed isolation / stale-context FK prevention / historical TypeScript regression contracts',$php.' '.$quote($root.'/scripts/n1-target-tenant-seed-typescript-contract-verify.php'));
    $run('tenant-execution-contract','Queue/scheduler tenant execution isolation / active-tenant / transactional default seed contracts',$php.' '.$quote($root.'/scripts/n1-target-tenant-execution-contract-verify.php'));
    $run('fresh-install-dependency-trust-contract','Fresh-install deterministic dependency bootstrap / review provenance sync contracts',$php.' '.$quote($root.'/scripts/n1-target-fresh-install-dependency-trust-contract-verify.php'));
    $run('installation-commit-contract','Sealed installation lock / staged receipt / crash-safe commit contracts',$php.' '.$quote($root.'/scripts/n1-target-installation-commit-contract-verify.php'));
    $run('installer-consent-flow-contract','Installer dependency preflight / DB consent / final CTA / password-risk consent contracts',$php.' '.$quote($root.'/scripts/n1-target-installer-consent-flow-contract-verify.php'));
    $run('installation-resume-fast-track-contract','Exact interrupted-install resume provenance + safe target fast-track contracts',$php.' '.$quote($root.'/scripts/n1-target-installation-resume-fast-track-contract-verify.php'));
    $run('target-progress-visibility-contract','Granular C1-C6 progress + historical 76-error TypeScript remediation ledger contracts',$php.' '.$quote($root.'/scripts/n1-target-progress-visibility-contract-verify.php'));
    $run('source-activation-contract','Exact executing Installer.php SHA / protocol / source-generation and stale-web-process fail-closed contracts',$php.' '.$quote($root.'/scripts/n1-target-source-activation-contract-verify.php'));
    $run('source-set-web-ack-contract','Critical installer source-set integrity / CLI-web activation nonce / installation-progress visibility contracts',$php.' '.$quote($root.'/scripts/n1-target-source-set-handshake-contract-verify.php'));
    $run('runtime-source-convergence-contract','Loaded critical PHP generation sentinels / secure one-time web acknowledgement / public diagnostic redaction contracts',$php.' '.$quote($root.'/scripts/n1-target-runtime-source-convergence-contract-verify.php'));
    $run('installer-host-clock-contract','Early installer-safe host/clock attestation / Windows umask portability / strict certification separation contracts',$php.' '.$quote($root.'/scripts/n1-target-installer-host-clock-contract-verify.php'));
    $run('installer-runtime-readiness-contract','Early source/dependency/host/resource/policy/process/activation installer readiness / strict certification separation contracts',$php.' '.$quote($root.'/scripts/n1-target-installer-runtime-readiness-contract-verify.php'));
    $run('install-runtime-handoff-contract','Full source-tree install identity / runtime admission split / post-install first-request handoff contracts',$php.' '.$quote($root.'/scripts/n1-target-install-runtime-handoff-contract-verify.php'));
    $run('clock-temp-portability-contract','Timezone-safe database epoch / Windows-safe writable installer temp fallback contracts',$php.' '.$quote($root.'/scripts/n1-target-clock-temp-portability-contract-verify.php'));
    $run('exact-resume-commit-contract','Full-source interrupted-install provenance / stable final source+dependency snapshot / committed-runtime recovery contracts',$php.' '.$quote($root.'/scripts/n1-target-exact-resume-commit-contract-verify.php'));
    $run('transactional-lock-intake-contract','Isolated dependency lock candidate refresh / explicit reviewed pair promotion / rollback contracts',$php.' '.$quote($root.'/scripts/n1-target-transactional-lock-intake-contract-verify.php'));
    $run('reproducible-dependency-toolchain-contract','Double-run dependency lock reproducibility / exact toolchain binding / locked-install immutability contracts',$php.' '.$quote($root.'/scripts/n1-target-reproducible-dependency-toolchain-contract-verify.php'));
    $run('dependency-candidate-supply-chain-contract','Candidate registry/source provenance + Composer/npm audit + pre-mutation promotion revalidation contracts',$php.' '.$quote($root.'/scripts/n1-target-dependency-candidate-supply-chain-contract-verify.php'));
    $run('semantic-lock-reproducibility-contract','Independent A/B lock semantic reproducibility + raw candidate sealing contracts',$php.' '.$quote($root.'/scripts/n1-target-semantic-lock-reproducibility-contract-verify.php'));
    $run('typescript-depth-contract','Recursive Inertia form depth boundaries for observed TS2589 build failures',$php.' '.$quote($root.'/scripts/n1-target-typescript-depth-contract-verify.php'));
    $run('windows-npm-bridge-contract','Windows npm.cmd/npx.cmd executable normalization + executed-payload toolchain fingerprint contracts',$php.' '.$quote($root.'/scripts/n1-target-windows-npm-bridge-contract-verify.php'));
    $run('npm-bundled-integrity-contract','npm package-lock v3 inBundle integrity coverage + bundle-owner SRI admission contracts',$php.' '.$quote($root.'/scripts/n1-target-npm-bundled-integrity-contract-verify.php'));
    $run('pkg1-usable-closure-contract','PKG-1 C1 14/14 + installer 100% + post-install + live login/admin non-destructive smoke contracts',$php.' '.$quote($root.'/scripts/pkg1-closure-contract-verify.php'));
    $run('environment-contract','Environment/config-cache/secrets source contracts',$php.' '.$quote($root.'/scripts/environment-contract-verify.php'));
    $run('dependency-contract','Dependency reproducibility/lockfile source contracts',$php.' '.$quote($root.'/scripts/dependency-contract-verify.php'));
    $run('filesystem-contract','Filesystem/path/atomic-write portability contracts',$php.' '.$quote($root.'/scripts/filesystem-contract-verify.php'));
    $run('transfer-contract','Large-file/streaming/quota transfer contracts',$php.' '.$quote($root.'/scripts/transfer-contract-verify.php'));
    $run('runtime-safety-contract','PHP/HTTP/proxy/queue/long-running runtime safety contracts',$php.' '.$quote($root.'/scripts/runtime-safety-contract-verify.php'));
    $run('final-integrity-contract','RC20 final closure/integrity source contracts',$php.' '.$quote($root.'/scripts/final-integrity-contract-verify.php'));
    $run('source-attestation-contract','Source-tree attestation/integrity contracts',$php.' '.$quote($root.'/scripts/source-attestation-contract-verify.php'));
    $run('concurrency-contract','Database transaction/deadlock/idempotency/concurrency contracts',$php.' '.$quote($root.'/scripts/concurrency-contract-verify.php'));
    $run('security-contract','Authentication/session/CSRF/tenant security contracts',$php.' '.$quote($root.'/scripts/security-contract-verify.php'));
    $run('frontend-contract','Frontend/runtime contract regression gate',$php.' '.$quote($root.'/scripts/frontend-contract-verify.php'));
    $run('source-guard','Nexora Source Guard',$php.' '.$quote($root.'/scripts/source-guard.php').' --source-only');
    $run('source-attestation-capture','Capture exact certified source tree',$command([PHP_BINARY,'scripts/source-attestation.php','--write','--expect='.$sourceAttestationInitial['tree_sha256']]));

    if ($sourceOnly) {
        $payload=$writeReport('source-pass',$steps,'Source-only certification passed. Dependency-backed and operator/browser gates are not certified by this mode.');
        fwrite(STDOUT,"\n[Nexora RC] SOURCE PASS — {$version}\nReport: {$reportDir}/latest.json\n");
        exit(0);
    }

    foreach ([
        $root.'/composer.lock'=>'Composer lockfile (composer.lock)',
        $root.'/package-lock.json'=>'npm lockfile (package-lock.json)',
        $root.'/vendor/autoload.php'=>'Composer dependencies (vendor/autoload.php)',
        $root.'/node_modules'=>'Node dependencies (node_modules)',
    ] as $path=>$label) {
        if(!file_exists($path)) throw new RuntimeException("{$label} missing. Install the reviewed lockfiles first, then run composer install and npm ci before full certification.");
    }

    $run('dependency-lock-review','Reviewed dependency lock attestation',$command([PHP_BINARY,'scripts/dependency-lock-review.php','--verify-attestation']));
    $run('dependency-lock-strict','Dependency lockfile integrity',$command([PHP_BINARY,'scripts/dependency-contract-verify.php','--strict-locks']));
    $run('dependency-runtime','PHP/Composer/Node/npm certified runtime ranges',$command([PHP_BINARY,'scripts/dependency-runtime-verify.php']));
    $run('composer-validate','Composer manifest validation',$command(['composer','validate','--strict','--no-check-publish']));
    $run('package-discover','Laravel package discovery',$command([PHP_BINARY,'artisan','package:discover','--ansi']));
    $run('optimize-clear-before','Clear framework caches',$command([PHP_BINARY,'artisan','optimize:clear']));
    $run('filesystem-doctor','Filesystem runtime doctor',$command([PHP_BINARY,'artisan','nexora:filesystem:doctor']));
    $run('transfer-doctor','Transfer staging/capacity/bounded-stream doctor',$command([PHP_BINARY,'artisan','nexora:transfer:doctor']));
    $run('runtime-doctor','PHP/request/proxy/queue runtime limits doctor',$command([PHP_BINARY,'artisan','nexora:runtime:doctor']));
    $run('concurrency-doctor','Database concurrency/idempotency runtime doctor',$command([PHP_BINARY,'artisan','nexora:concurrency:doctor']));
    $run('route-boot','Route registry boot',$command([PHP_BINARY,'artisan','route:list']));
    $run('schedule-boot','Scheduler registry boot',$command([PHP_BINARY,'artisan','schedule:list']));
    $run('database-prepare','Prepare isolated certification database',$command([PHP_BINARY,'scripts/create-certification-database.php']));
    $run('database-version-doctor','Primary database server minimum-version doctor',$command([PHP_BINARY,'artisan','nexora:database:doctor']));
    $run('migrate-fresh','Fresh migration',$command([PHP_BINARY,'artisan','migrate:fresh','--force']));
    $run('migrate-seed','Fresh migration + seed',$command([PHP_BINARY,'artisan','migrate:fresh','--seed','--force']));
    $run('seed-idempotency','Repeat seeding without row-count drift',$command([PHP_BINARY,'artisan','db:seed','--force']));
    $run('migration-reset','Full migration rollback/down round-trip',$command([PHP_BINARY,'artisan','migrate:reset','--force']));
    $run('migration-rebuild','Rebuild all migrations after reset',$command([PHP_BINARY,'artisan','migrate','--force']));
    $run('seed-rebuild','Seed rebuilt database',$command([PHP_BINARY,'artisan','db:seed','--force']));
    $run('runtime-sync','Runtime registry synchronization',$command([PHP_BINARY,'artisan','nexora:runtime:sync']));
    $run('runtime-cache','Runtime cache compilation',$command([PHP_BINARY,'artisan','nexora:runtime:cache']));
    $run('php-tests','Laravel/PHPUnit test suite',$command([PHP_BINARY,'artisan','test']));
    $requiredMatrixDrivers=['mysql','mariadb','pgsql','sqlite','sqlsrv'];
    $matrix=trim((string)getenv('NEXORA_CERT_DB_MATRIX'));
    if($finalEvidenceRequired){
        if($matrix==='') $matrix=implode(',',$requiredMatrixDrivers);
        $requested=array_values(array_unique(array_filter(array_map('trim',explode(',',strtolower($matrix))))));
        $missingMatrix=array_values(array_diff($requiredMatrixDrivers,$requested));
        if($missingMatrix!==[]) throw new RuntimeException('Final certification requires the full primary DB matrix. Missing: '.implode(', ',$missingMatrix));
    }
    if($matrix!=='') $run('database-matrix','Database compatibility matrix',$command([PHP_BINARY,'scripts/certify-database-matrix.php','--drivers='.$matrix,'--strict']));
    else $steps[]=['id'=>'database-matrix','label'=>'Database compatibility matrix','status'=>'skip','required'=>false,'exit_code'=>0,'duration_seconds'=>0.0,'stdout_tail'=>'Set NEXORA_CERT_DB_MATRIX=mysql,mariadb,pgsql,sqlite,sqlsrv to enable strict matrix certification. Final mode requires all five primary families.','stderr_tail'=>''];
    $run('pint','Laravel Pint',$command([PHP_BINARY,'vendor/bin/pint','--test']));
    $run('npm-typecheck','TypeScript strict typecheck',$command(['npm','run','typecheck']));
    $run('npm-tests','Frontend unit/component tests',$command(['npm','run','test']));
    $run('npm-build','Production Vite build',$command(['npm','run','build']));
    $run('dependency-provenance','Locked dependency provenance/integrity report',$command([PHP_BINARY,'scripts/dependency-provenance.php']));
    $run('dependency-audit','Composer/npm vulnerability audit against locked graph',$command([PHP_BINARY,'scripts/dependency-audit.php']));
    $run('performance-build','Production build manifest + asset budgets',$command([PHP_BINARY,'scripts/performance-build-verify.php']));
    $run('artisan-optimize','Production optimization/cache compilation',$command([PHP_BINARY,'artisan','optimize']));
    $run('artisan-optimize-boot','Optimized framework boot',$command([PHP_BINARY,'artisan','about']));
    $run('artisan-route-boot-cached','Route registry boot with production caches',$command([PHP_BINARY,'artisan','route:list']));
    $run('artisan-schedule-boot-cached','Scheduler registry boot with production caches',$command([PHP_BINARY,'artisan','schedule:list']));
    $run('environment-doctor-cached','Environment/config-cache doctor under optimized boot',$command([PHP_BINARY,'artisan','nexora:environment:doctor']));
    $run('filesystem-doctor-cached','Filesystem doctor under optimized boot',$command([PHP_BINARY,'artisan','nexora:filesystem:doctor']));
    $run('transfer-doctor-cached','Transfer doctor under optimized boot',$command([PHP_BINARY,'artisan','nexora:transfer:doctor']));
    $run('runtime-doctor-cached','Runtime limits doctor under optimized boot',$command([PHP_BINARY,'artisan','nexora:runtime:doctor']));
    $run('concurrency-doctor-cached','Concurrency doctor under optimized boot',$command([PHP_BINARY,'artisan','nexora:concurrency:doctor']));
    $run('database-version-doctor-cached','Primary database version doctor under optimized boot',$command([PHP_BINARY,'artisan','nexora:database:doctor']));

    if ((string) getenv('NEXORA_CERT_BASE_URL') !== '') {
        $run('http-smoke','HTTP headers/cache/performance/live-ready-login smoke',$command([PHP_BINARY,'scripts/http-smoke.php']));
    } else {
        $steps[]=['id'=>'http-smoke','label'=>'HTTP headers/cache/performance/live-ready-login smoke','status'=>'skip','required'=>false,'exit_code'=>0,'duration_seconds'=>0.0,'stdout_tail'=>'NEXORA_CERT_BASE_URL not configured. Target-server HTTP performance/header evidence remains pending.','stderr_tail'=>''];
    }
    $run('artisan-optimize-clear','Clear generated optimization cache after optimized boot/smoke',$command([PHP_BINARY,'artisan','optimize:clear']));

    if ($finalEvidenceRequired || (string) getenv('NEXORA_CERT_BROWSER_EVIDENCE') === '1') {
        $run('browser-evidence','Responsive/RTL/theme/accessibility operator browser evidence',$command([PHP_BINARY,'scripts/browser-evidence-verify.php']));
    } else {
        $steps[]=['id'=>'browser-evidence','label'=>'Responsive/RTL/theme/accessibility operator browser evidence','status'=>'skip','required'=>false,'exit_code'=>0,'duration_seconds'=>0.0,'stdout_tail'=>'Set NEXORA_CERT_BROWSER_EVIDENCE=1 after recording browser evidence to make this gate required.','stderr_tail'=>''];
    }

    if ($finalEvidenceRequired) {
        $run('zero-install-evidence','Observed browser zero-install/recovery operator evidence',$command([PHP_BINARY,'scripts/zero-install-evidence-verify.php']));
        $run('upgrade-rehearsal-evidence','Existing-install upgrade rehearsal operator evidence',$command([PHP_BINARY,'scripts/upgrade-rehearsal-evidence-verify.php']));
        $run('backup-restore-evidence','Disposable-target backup/restore operator evidence',$command([PHP_BINARY,'scripts/backup-restore-evidence-verify.php']));
        $run('ha-evidence','Multi-node HA operator evidence',$command([PHP_BINARY,'scripts/ha-evidence-verify.php']));
        $run('final-evidence','Final N1.0 evidence aggregation',$command([PHP_BINARY,'scripts/final-evidence-verify.php']));
    } else {
        $steps[]=['id'=>'zero-install-evidence','label'=>'Observed browser zero-install/recovery operator evidence','status'=>'skip','required'=>false,'exit_code'=>0,'duration_seconds'=>0.0,'stdout_tail'=>'Final mode requires observed fresh-install + interrupted-recovery evidence.','stderr_tail'=>''];
        $steps[]=['id'=>'upgrade-rehearsal-evidence','label'=>'Existing-install upgrade rehearsal operator evidence','status'=>'skip','required'=>false,'exit_code'=>0,'duration_seconds'=>0.0,'stdout_tail'=>'Final mode requires a disposable existing-install upgrade rehearsal.','stderr_tail'=>''];
        $steps[]=['id'=>'backup-restore-evidence','label'=>'Disposable-target backup/restore operator evidence','status'=>'skip','required'=>false,'exit_code'=>0,'duration_seconds'=>0.0,'stdout_tail'=>'Set NEXORA_CERT_FINAL_EVIDENCE=1 after recording zero-install + upgrade + browser + HTTP/build + five-DB matrix + backup/restore + HA evidence.','stderr_tail'=>''];
        $steps[]=['id'=>'ha-evidence','label'=>'Multi-node HA operator evidence','status'=>'skip','required'=>false,'exit_code'=>0,'duration_seconds'=>0.0,'stdout_tail'=>'Real independent-node evidence remains pending.','stderr_tail'=>''];
        $steps[]=['id'=>'final-evidence','label'=>'Final N1.0 evidence aggregation','status'=>'skip','required'=>false,'exit_code'=>0,'duration_seconds'=>0.0,'stdout_tail'=>'Final evidence aggregation is required for production packaging and N1.0 closure.','stderr_tail'=>''];
    }

    $run('source-attestation-final','Re-verify certified source tree did not drift',$command([PHP_BINARY,'scripts/source-attestation.php','--expect='.$sourceAttestationInitial['tree_sha256']]));

    if ($failed) throw new RuntimeException('One or more required certification gates failed.');

    // Write the matching pass record before packaging; the production release builder independently validates it.
    $writeReport('certification-pass',$steps,'All automated required certification gates passed. Final operator evidence is sealed when NEXORA_CERT_FINAL_EVIDENCE=1.');
    if (! $skipPackage && $finalEvidenceRequired) {
        $run('production-package','Certified production release packaging',$command([PHP_BINARY,'scripts/build-production-release.php']));
        $run('production-package-verify','Reopen and independently verify production release artifact',$command([PHP_BINARY,'scripts/release-artifact-verify.php']));
    } else {
        $reason = $skipPackage ? 'Skipped by --no-package.' : 'Production packaging remains locked until NEXORA_CERT_FINAL_EVIDENCE=1 and all operator evidence passes.';
        $steps[]=['id'=>'production-package','label'=>'Certified production release packaging','status'=>'skip','required'=>false,'exit_code'=>0,'duration_seconds'=>0.0,'stdout_tail'=>$reason,'stderr_tail'=>''];
    }

    $status = $failed ? 'certification-fail' : 'certification-pass';
    $writeReport($status,$steps,$status==='certification-pass'?'Automated certification passed. N1.0 closes only when final evidence aggregation is also PASS.':'Certification failed.');
    fwrite(STDOUT,"\n[Nexora RC] ".strtoupper($status)." — {$version}\nReport: {$reportDir}/latest.json\n");
    exit($failed?1:0);
} catch (Throwable $exception) {
    if (!$failed) {
        $steps[]=['id'=>'runner','label'=>'Certification runner','status'=>'fail','required'=>true,'exit_code'=>1,'duration_seconds'=>0.0,'stdout_tail'=>'','stderr_tail'=>$exception->getMessage()];
        $writeReport('certification-fail',$steps,$exception->getMessage());
    }
    fwrite(STDERR,"\n[Nexora RC] FAILED: {$exception->getMessage()}\nReport: {$reportDir}/latest.json\n");
    exit(1);
}
