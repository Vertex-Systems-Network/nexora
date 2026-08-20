<?php

declare(strict_types=1);

namespace App\Nexora\Data;

final class ConnectionCatalog
{
    /** @return array<string,array<string,mixed>> */
    public function all(): array
    {
        $mongo = extension_loaded('mongodb') && class_exists(\MongoDB\Driver\Manager::class);
        $redis = (extension_loaded('redis') && class_exists(\Redis::class)) || class_exists(\Predis\Client::class);
        $aws = class_exists(\Aws\Sdk::class);

        return [
            'mongodb' => $this->item('mongodb', 'MongoDB', 'Document database', 'mongodb', $mongo, 'MongoDB PHP extension', 'mongodb://127.0.0.1:27017'),
            'mongodb_atlas' => $this->item('mongodb_atlas', 'MongoDB Atlas', 'Managed document database', 'mongodb', $mongo, 'MongoDB PHP extension', 'mongodb+srv://cluster.example.mongodb.net'),
            'redis' => $this->item('redis', 'Redis', 'Cache · queues · realtime', 'redis', $redis, 'PhpRedis extension or Predis client', '127.0.0.1:6379'),
            'aws_documentdb' => $this->item('aws_documentdb', 'Amazon DocumentDB', 'AWS document database', 'aws', $mongo, 'MongoDB PHP extension', 'cluster.cluster-xxxx.region.docdb.amazonaws.com:27017'),
            'aws_elasticache_redis' => $this->item('aws_elasticache_redis', 'Amazon ElastiCache for Redis', 'AWS cache · realtime', 'aws', $redis, 'PhpRedis extension or Predis client', 'cache.xxxxxx.cache.amazonaws.com:6379'),
            'aws_dynamodb' => $this->item('aws_dynamodb', 'Amazon DynamoDB', 'AWS key-value · document', 'aws', $aws, 'AWS SDK connector', 'region: us-east-1'),
        ];
    }

    /** @return array<string,mixed> */
    public function get(string $key): array
    {
        $all = $this->all();
        if (! isset($all[$key])) {
            throw new \InvalidArgumentException('Unknown data service: '.$key);
        }
        return $all[$key];
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->all());
    }

    /** @return array<string,mixed> */
    private function item(string $key, string $label, string $kind, string $provider, bool $available, string $requirement, string $example): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'kind' => $kind,
            'description' => $kind.' · '.($available ? 'runtime available' : 'adapter required'),
            'provider' => $provider,
            'available' => $available,
            'requirement' => $requirement,
            'example' => $example,
            'availability_message' => $available ? 'Connector runtime is available.' : 'Connector runtime is not installed yet: '.$requirement.'.',
        ];
    }
}
