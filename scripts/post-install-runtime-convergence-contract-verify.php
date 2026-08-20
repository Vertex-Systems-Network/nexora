<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required post-install runtime source file missing: {$relative}";
        return '';
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = "Unable to read post-install runtime source file: {$relative}";
        return '';
    }

    return $contents;
};

$routes = $read('routes/web.php');
$controller = $read('app/Http/Controllers/Install/InstallerController.php');
$handoff = $read('app/Nexora/Installation/RuntimePostInstallHandoff.php');

foreach ([
    "Route::get('/runtime-handoff', [InstallerController::class, 'runtimeHandoff'])" => 'fresh HTTP runtime-handoff route',
    '->withoutMiddleware([RuntimeNodeHeartbeat::class, ResolveEnterpriseOrganization::class, HandleInertiaRequests::class])' => 'installer route bootstrap isolation',
] as $needle => $label) {
    if ($routes !== '' && ! str_contains($routes, $needle)) {
        $errors[] = "Post-install convergence route contract missing: {$label}.";
    }
}

foreach ([
    'public function runtimeHandoff()' => 'runtimeHandoff controller action',
    '$this->postInstallHandoff->verifyAndRecord();' => 'fresh-request verification/sealing call',
    "redirect()->route('login')" => 'successful handoff login redirect',
] as $needle => $label) {
    if ($controller !== '' && ! str_contains($controller, $needle)) {
        $errors[] = "Post-install convergence controller contract missing: {$label}.";
    }
}

foreach ([
    'public function finalizeCommittedRuntimeIdentity()' => 'one-time runtime identity finalizer',
    "$allowed = ['environment', 'activation', 'service', 'process'];" => 'narrow reconciliable plane allow-list',
    '$hard = array_values(array_diff($mismatches, $allowed));' => 'immutable mismatch rejection',
    "if (($services['status'] ?? 'fail') !== 'pass')" => 'service plane health gate',
    "if (($processes['status'] ?? 'fail') !== 'pass')" => 'process policy health gate',
    "'post_install_identity_finalized' => true" => 'one-time finalized marker',
    '$after = $this->versions->assess();' => 'post-write compatibility reassessment',
    "if (($after['compatible'] ?? false) !== true)" => 'post-write fail-closed convergence check',
] as $needle => $label) {
    if ($handoff !== '' && ! str_contains($handoff, $needle)) {
        $errors[] = "Post-install convergence fail-closed contract missing: {$label}.";
    }
}

if ($handoff !== '' && preg_match('/\$allowed\s*=\s*\[([^\]]+)\]/', $handoff, $match) === 1) {
    preg_match_all("/'([^']+)'/", $match[1], $planes);
    $actual = array_values($planes[1] ?? []);
    $expected = ['environment', 'activation', 'service', 'process'];
    sort($actual);
    sort($expected);
    if ($actual !== $expected) {
        $errors[] = 'Post-install convergence allow-list changed; only environment, activation, service and process may be reconciled.';
    }
} else {
    $errors[] = 'Post-install convergence allow-list could not be parsed.';
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Post-Install Runtime Convergence Contract] FAILED\n - ".implode("\n - ", $errors)."\n");
    exit(1);
}

fwrite(STDOUT, "[Nexora Post-Install Runtime Convergence Contract] PASS — fresh-request sealing is present and only environment/activation/service/process may reconcile; immutable planes remain fail-closed.\n");
