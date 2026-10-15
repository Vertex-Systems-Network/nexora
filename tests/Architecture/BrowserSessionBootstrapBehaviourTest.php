<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A real fresh PHP process is required: the pre-Laravel bootstrap runs before
 * PHPUnit can inspect the session driver, and defines process-wide constants.
 */
final class BrowserSessionBootstrapBehaviourTest extends TestCase
{
    #[Test]
    public function persistent_sessions_are_allowed_only_for_a_disposable_local_browser_fixture(): void
    {
        $root = sys_get_temp_dir().'/nexora-session-guard-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($root.'/bootstrap', 0700, true));
        try {
            self::assertTrue(copy(base_path('bootstrap/nexora-runtime-bootstrap.php'), $root.'/bootstrap/nexora-runtime-bootstrap.php'));
            self::assertTrue(copy(base_path('bootstrap/nexora-installer-bootstrap.php'), $root.'/bootstrap/nexora-installer-bootstrap.php'));
            $base = [
                'APP_ENV' => 'testing',
                'NEXORA_INSTALLER_BYPASS' => 'true',
                'NEXORA_CI_BROWSER_E2E' => '1',
                'DB_CONNECTION' => 'mysql',
                'DB_DATABASE' => 'nexora_testing',
                'DB_HOST' => '127.0.0.1',
                'APP_URL' => 'http://127.0.0.1:8974',
                'APP_KEY' => 'base64:'.str_repeat('A', 43).'=', // disposable fixture only
                'SESSION_DRIVER' => 'redis',
                'CACHE_STORE' => 'database',
                'QUEUE_CONNECTION' => 'redis',
            ];
            $cases = [
                'explicit disposable loopback' => [[], 'file'],
                'another permitted loopback DB hostname' => [['DB_HOST' => 'localhost'], 'file'],
                'no browser opt-in' => [['NEXORA_CI_BROWSER_E2E' => '0'], 'array'],
                'different database name' => [['DB_DATABASE' => 'production'], 'array'],
                'remote database host' => [['DB_HOST' => 'db.example.invalid'], 'array'],
                'different database driver' => [['DB_CONNECTION' => 'pgsql'], 'array'],
                'remote browser origin' => [['APP_URL' => 'https://nexora.example.invalid'], 'array'],
                'non-loopback HTTP origin' => [['APP_URL' => 'http://192.0.2.10:8974'], 'array'],
                'testing bypass absent' => [['NEXORA_INSTALLER_BYPASS' => 'false'], 'file'],
            ];
            foreach ($cases as $name => [$changes, $expected]) {
                $result = $this->bootDisposableChild($root, array_replace($base, $changes));
                self::assertSame($expected, $result['session'] ?? null, $name);
                if (($changes['NEXORA_INSTALLER_BYPASS'] ?? 'true') === 'true') {
                    self::assertSame('array', $result['cache'] ?? null, $name.' must isolate cache');
                    self::assertSame('sync', $result['queue'] ?? null, $name.' must isolate queues');
                }
            }
        } finally {
            File::deleteDirectory($root);
        }
    }

    /** @param array<string,string> $environment
     *  @return array<string,mixed>
     */
    private function bootDisposableChild(string $root, array $environment): array
    {
        $script = 'require '.var_export($root.'/bootstrap/nexora-installer-bootstrap.php', true).';'
            .'echo json_encode(["session"=>getenv("SESSION_DRIVER"),"cache"=>getenv("CACHE_STORE"),"queue"=>getenv("QUEUE_CONNECTION")], JSON_THROW_ON_ERROR);';
        $process = proc_open(
            [PHP_BINARY, '-r', $script],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root,
            $environment,
        );
        self::assertIsResource($process, 'Cannot spawn a disposable bootstrap PHP process');
        fclose($pipes[0]);
        $stdout = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), 'Disposable bootstrap must exit cleanly: '.substr($stderr, 0, 200));
        $result = json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($result);

        return $result;
    }
}
