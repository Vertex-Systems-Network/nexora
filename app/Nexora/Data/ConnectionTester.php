<?php

declare(strict_types=1);

namespace App\Nexora\Data;

use App\Models\DataConnection;
use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;
use Redis;
use RuntimeException;
use Throwable;

final class ConnectionTester
{
    /** @return array{ok:bool,message:string} */
    public function test(DataConnection $connection): array
    {
        $secret = (array) ($connection->secret_payload ?? []);
        $options = (array) ($connection->options ?? []);

        return $this->testPayload([
            'driver' => (string) $connection->driver,
            'endpoint' => (string) ($connection->endpoint ?? ''),
            'database' => (string) ($connection->database ?? ''),
            'username' => (string) ($connection->username ?? ($secret['username'] ?? '')),
            'password' => (string) ($secret['password'] ?? ''),
            'region' => (string) ($options['region'] ?? ''),
            'access_key' => (string) ($secret['key'] ?? ''),
            'secret_key' => (string) ($secret['secret'] ?? ''),
        ]);
    }

    /** @param array<string,mixed> $payload @return array{ok:bool,message:string} */
    public function testPayload(array $payload): array
    {
        try {
            $driver = (string) ($payload['driver'] ?? '');
            return match ($driver) {
                'mongodb', 'mongodb_atlas', 'aws_documentdb' => $this->mongo($payload),
                'redis', 'aws_elasticache_redis' => $this->redis($payload),
                'aws_dynamodb' => $this->dynamo($payload),
                default => throw new RuntimeException('No runtime tester is registered for this connector.'),
            };
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'message' => $this->safe($exception->getMessage(), $payload),
            ];
        }
    }

    /** @param array<string,mixed> $payload */
    private function mongo(array $payload): array
    {
        if (! extension_loaded('mongodb') || ! class_exists(Manager::class)) {
            throw new RuntimeException('MongoDB PHP extension is not installed.');
        }
        $uri = trim((string) ($payload['endpoint'] ?? ''));
        if ($uri === '') {
            throw new RuntimeException('MongoDB connection string is required.');
        }
        if ($this->hasEmbeddedCredentials($uri)) {
            throw new RuntimeException('Embedded endpoint credentials are not allowed. Store username and password in encrypted credential fields.');
        }

        $uriOptions = [];
        $username = trim((string) ($payload['username'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        if ($username !== '') $uriOptions['username'] = $username;
        if ($password !== '') $uriOptions['password'] = $password;

        $manager = new Manager($uri, $uriOptions);
        $database = trim((string) ($payload['database'] ?? '')) ?: 'admin';
        $cursor = $manager->executeCommand($database, new Command(['ping' => 1]));
        $cursor->toArray();
        return ['ok' => true, 'message' => 'MongoDB-compatible service responded to ping.'];
    }

    /** @param array<string,mixed> $payload */
    private function redis(array $payload): array
    {
        $endpoint = trim((string) ($payload['endpoint'] ?? ''));
        if ($this->hasEmbeddedCredentials($endpoint)) {
            throw new RuntimeException('Embedded endpoint credentials are not allowed. Store username and password in encrypted credential fields.');
        }
        [$host, $port] = $this->hostPort($endpoint, 6379);
        $username = (string) ($payload['username'] ?? '');
        $password = (string) ($payload['password'] ?? '');

        if (extension_loaded('redis') && class_exists(Redis::class)) {
            $redis = new Redis();
            if (! $redis->connect($host, $port, 5.0)) {
                throw new RuntimeException('Redis connection could not be opened.');
            }
            if ($password !== '') {
                $auth = $username !== '' ? [$username, $password] : $password;
                if (! $redis->auth($auth)) {
                    throw new RuntimeException('Redis authentication failed.');
                }
            }
            $reply = $redis->ping();
            $redis->close();
            return ['ok' => true, 'message' => 'Redis responded successfully: '.(is_string($reply) ? $reply : 'PONG')];
        }

        if (class_exists(\Predis\Client::class)) {
            $parameters = [
                'scheme' => str_starts_with(strtolower($endpoint), 'rediss://') ? 'tls' : 'tcp',
                'host' => $host,
                'port' => $port,
                'timeout' => 5.0,
            ];
            if ($username !== '') $parameters['username'] = $username;
            if ($password !== '') $parameters['password'] = $password;
            $client = new \Predis\Client($parameters);
            $reply = $client->ping();
            return ['ok' => true, 'message' => 'Redis responded successfully: '.(string) $reply];
        }

        throw new RuntimeException('Neither the PhpRedis extension nor the Predis client is installed.');
    }

    /** @param array<string,mixed> $payload */
    private function dynamo(array $payload): array
    {
        if (! class_exists(\Aws\DynamoDb\DynamoDbClient::class)) {
            throw new RuntimeException('AWS SDK connector is not installed.');
        }
        $config = [
            'version' => 'latest',
            'region' => trim((string) ($payload['region'] ?? '')) ?: 'us-east-1',
        ];
        $accessKey = (string) ($payload['access_key'] ?? '');
        $secretKey = (string) ($payload['secret_key'] ?? '');
        if ($accessKey !== '' && $secretKey !== '') {
            $config['credentials'] = ['key' => $accessKey, 'secret' => $secretKey];
        }
        /** @var \Aws\DynamoDb\DynamoDbClient $client */
        $client = new \Aws\DynamoDb\DynamoDbClient($config);
        $client->listTables(['Limit' => 1]);
        return ['ok' => true, 'message' => 'Amazon DynamoDB API connection succeeded.'];
    }

    /** @return array{0:string,1:int} */
    private function hostPort(string $endpoint, int $defaultPort): array
    {
        $trimmed = trim($endpoint);
        if ($trimmed === '') return ['127.0.0.1', $defaultPort];

        if (str_contains($trimmed, '://')) {
            $parts = parse_url($trimmed);
            if (is_array($parts) && isset($parts['host'])) {
                return [
                    (string) $parts['host'],
                    isset($parts['port']) ? (int) $parts['port'] : $defaultPort,
                ];
            }
        }

        $parts = explode(':', $trimmed, 2);
        return [
            $parts[0] ?: '127.0.0.1',
            isset($parts[1]) && is_numeric($parts[1]) ? (int) $parts[1] : $defaultPort,
        ];
    }

    private function hasEmbeddedCredentials(string $endpoint): bool
    {
        return preg_match('#^[a-z][a-z0-9+.-]*://[^/@\s]+@#i', trim($endpoint)) === 1;
    }

    /** @param array<string,mixed> $payload */
    private function safe(string $message, array $payload): string
    {
        $safe = preg_replace(
            '#([a-z][a-z0-9+.-]*://)([^/@\s]+)@#i',
            '$1[redacted]@',
            $message,
        ) ?? $message;
        $safe = preg_replace(
            '/(password|secret|token|key)=([^\s;&]+)/i',
            '$1=[redacted]',
            $safe,
        ) ?? $safe;

        foreach (['password', 'access_key', 'secret_key'] as $key) {
            $secret = (string) ($payload[$key] ?? '');
            if ($secret !== '' && mb_strlen($secret) >= 4) {
                $safe = str_replace($secret, '[redacted]', $safe);
            }
        }

        return trim($safe) !== '' ? $safe : 'Connection test failed.';
    }
}
