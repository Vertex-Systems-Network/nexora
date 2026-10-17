<?php

declare(strict_types=1);

require_once __DIR__.'/../scripts/phase1-exposed-key-audit.php';

$root = sys_get_temp_dir().'/nexora-ref-contract-'.bin2hex(random_bytes(8));
$scanner = realpath(__DIR__.'/../scripts/phase1-exposed-key-audit.php');
if (!is_string($scanner)) { throw new RuntimeException('Scanner unavailable'); }
mkdir($root.'/bootstrap', 0700, true);
file_put_contents($root.'/artisan', '<?php');
file_put_contents($root.'/bootstrap/app.php', '<?php');
$referenceFile = $root.'/private-reference';
$safe = 'base64:'.base64_encode(str_repeat('S',32));
$exposed = 'base64:'.base64_encode(str_repeat('L',32));
file_put_contents($root.'/.env', "APP_KEY={$safe}\n");
$assertions = 0;
$run = static function () use ($scanner,$referenceFile,$root): array {
    $cmd = escapeshellarg(PHP_BINARY).' '.escapeshellarg($scanner)
        .' --reference-file='.escapeshellarg($referenceFile)
        .' --target='.escapeshellarg($root).' 2>&1';
    exec($cmd, $output, $status);
    return [$status,implode("\n",$output)];
};
$check = static function (bool $ok, string $label) use (&$assertions): void {
    if (!$ok) { throw new RuntimeException('FAIL reference contract: '.$label); }
    $assertions++;
};
try {
    // No invalid reference string may trigger a clean negative in the public API.
    foreach ([str_repeat('L',31),str_repeat('L',33),$exposed,''] as $invalid) {
        $threw = false;
        try { nexoraHistoricalAuditTarget($invalid,$root); }
        catch (InvalidArgumentException $e) {
            $threw = true;
            $check(!str_contains($e->getMessage(),str_repeat('L',16)), 'reference redacted');
        }
        $check($threw, 'invalid reference rejected');
    }
    file_put_contents($referenceFile,str_repeat('L',31));
    chmod($referenceFile,0600);
    [$status,$output] = $run();
    $check($status===2, 'invalid reference CLI exit 2');
    $check(!str_contains($output,str_repeat('L',16)), 'invalid secret not disclosed');
    file_put_contents($referenceFile,$exposed);
    chmod($referenceFile,0600);
    [$status,$output] = $run();
    $check($status===0, 'valid reference no-match CLI exit 0');
    $check(!str_contains($output,$exposed), 'valid secret not disclosed');
    file_put_contents($root.'/.env',"APP_KEY={$exposed}\n");
    [$status,$output] = $run();
    $check($status===3, 'valid reference exposed CLI exit 3');
    $check(!str_contains($output,$exposed), 'positive case secret not disclosed');
    unlink($root.'/.env');
    [$status,$output] = $run();
    $check($status===2, 'missing current key CLI exit 2');
    $check(!str_contains($output,$exposed), 'unknown status secret not disclosed');
    echo "PASS {$assertions}/{$assertions} direct-reference / CLI safety assertions (synthetic only)\n";
} finally {
    @unlink($root.'/.env');
    @unlink($referenceFile);
    @unlink($root.'/bootstrap/app.php');
    @unlink($root.'/artisan');
    @rmdir($root.'/bootstrap');
    @rmdir($root);
}
