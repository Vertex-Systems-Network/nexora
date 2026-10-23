<?php

declare(strict_types=1);

/**
 * SECURITY-BASELINE-200 / AppSec increment: fail-closed scan of git-tracked files for
 * high-confidence committed secrets. Output never prints the matched value, only
 * file:line and rule id. Use `--self-test` to prove every rule still fires.
 */

$root = dirname(__DIR__);

$rules = [
    'private-key-block' => '/-----BEGIN (?:RSA |EC |DSA |OPENSSH |ENCRYPTED |PGP )?PRIVATE KEY(?: BLOCK)?-----\r?\n(?:[A-Za-z-]+: [^\n]*\r?\n)*\r?\n?[A-Za-z0-9+\/=]{40,}/',
    'aws-access-key-id' => '/\b(?:AKIA|ASIA)[0-9A-Z]{16}\b/',
    'github-token' => '/\b(?:gh[pousr]_[A-Za-z0-9]{36,}|github_pat_[A-Za-z0-9_]{60,})\b/',
    'slack-token' => '/\bxox[abposr]-[A-Za-z0-9-]{10,}\b/',
    'stripe-live-key' => '/\b(?:sk|rk)_live_[A-Za-z0-9]{20,}\b/',
    'google-api-key' => '/\bAIza[0-9A-Za-z_-]{35}\b/',
    'anthropic-or-openai-key' => '/\bsk-(?:ant-[A-Za-z0-9_-]{20,}|proj-[A-Za-z0-9_-]{20,})\b/',
    'laravel-app-key' => '/^\s*APP_KEY\s*=\s*["\']?base64:[A-Za-z0-9+\/]{43}=/m',
];

if (in_array('--self-test', $argv, true)) {
    // Fixtures are assembled at runtime so this file never matches its own rules.
    $samples = [
        'private-key-block' => '-----BEGIN '.'RSA PRIVATE KEY-----'."\n".str_repeat('M', 64),
        'aws-access-key-id' => 'AKIA'.str_repeat('Q', 16),
        'github-token' => 'ghp_'.str_repeat('a', 36),
        'slack-token' => 'xoxb-'.str_repeat('1', 12),
        'stripe-live-key' => 'sk_'.'live_'.str_repeat('a', 24),
        'google-api-key' => 'AIza'.str_repeat('b', 35),
        'anthropic-or-openai-key' => 'sk-'.'ant-'.str_repeat('c', 24),
        'laravel-app-key' => 'APP_KEY=base64:'.str_repeat('d', 43).'=',
    ];
    $failed = [];
    foreach ($rules as $id => $pattern) {
        if (! isset($samples[$id]) || preg_match($pattern, $samples[$id]) !== 1) {
            $failed[] = $id;
        }
    }
    if (preg_match($rules['laravel-app-key'], 'APP_KEY=') === 1 || preg_match($rules['aws-access-key-id'], 'AKIAEXAMPLE') === 1
        || preg_match($rules['private-key-block'], '-----BEGIN '."PRIVATE KEY-----\nFAKE\n-----END PRIVATE KEY-----") === 1) {
        $failed[] = 'negative-controls';
    }
    if ($failed !== []) {
        fwrite(STDERR, '[Nexora Secret Scan] SELF-TEST FAIL — '.implode(', ', $failed).PHP_EOL);
        exit(1);
    }
    echo '[Nexora Secret Scan] SELF-TEST PASS — '.count($rules).' rules'.PHP_EOL;
    exit(0);
}

$list = [];
$exit = 0;
exec('git -C '.escapeshellarg($root).' ls-files -z 2>/dev/null', $list, $exit);
if ($exit !== 0 || $list === []) {
    fwrite(STDERR, '[Nexora Secret Scan] FAIL — cannot enumerate git-tracked files.'.PHP_EOL);
    exit(1);
}
$files = array_filter(explode("\0", implode("\n", $list)), static fn (string $f): bool => $f !== '');

$skipExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'zip', 'gz', 'pdf', 'mp4', 'webm'];
$skipFiles = ['package-lock.json', 'composer.lock', 'scripts/secret-scan.php'];

$findings = [];
$scanned = 0;
foreach ($files as $file) {
    if (in_array($file, $skipFiles, true) || in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $skipExtensions, true)) {
        continue;
    }
    $path = $root.'/'.$file;
    if (! is_file($path) || filesize($path) > 2_000_000) {
        continue;
    }
    $content = (string) file_get_contents($path);
    if (str_contains($content, "\0")) {
        continue;
    }
    $scanned++;
    foreach ($rules as $id => $pattern) {
        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE) > 0) {
            foreach ($matches[0] as [, $offset]) {
                $findings[] = $file.':'.(substr_count($content, "\n", 0, $offset) + 1).' '.$id;
            }
        }
    }
}

if ($findings !== []) {
    fwrite(STDERR, '[Nexora Secret Scan] FAIL — '.count($findings).' potential committed secret(s); values withheld:'.PHP_EOL);
    foreach ($findings as $finding) {
        fwrite(STDERR, '  '.$finding.PHP_EOL);
    }
    exit(1);
}

echo '[Nexora Secret Scan] PASS — '.$scanned.' tracked files, '.count($rules).' rules, 0 findings.'.PHP_EOL;
