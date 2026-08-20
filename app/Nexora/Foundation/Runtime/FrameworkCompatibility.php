<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Runtime;

use Illuminate\Foundation\Application;
use RuntimeException;

final class FrameworkCompatibility
{
    /** @return array<string, mixed> */
    public function status(): array
    {
        $version = Application::VERSION;
        $minimum = (string) config('nexora-framework.laravel.minimum', '13.24.0');
        $maximumExclusive = (string) config('nexora-framework.laravel.maximum_exclusive', '14.0.0');

        $minimumSatisfied = version_compare($version, $minimum, '>=');
        $maximumSatisfied = version_compare($version, $maximumExclusive, '<');
        $expectedConstraint = (string) config(
            'nexora-framework.laravel.composer_constraint',
            '^13.24',
        );
        $manifestConstraint = $this->manifestConstraint();
        $manifestCompatible = $manifestConstraint === $expectedConstraint;

        return [
            'status' => $minimumSatisfied && $maximumSatisfied && $manifestCompatible ? 'pass' : 'fail',
            'framework' => 'laravel/framework',
            'installed_version' => $version,
            'minimum_version' => $minimum,
            'maximum_exclusive' => $maximumExclusive,
            'composer_constraint' => $expectedConstraint,
            'manifest_constraint' => $manifestConstraint,
            'manifest_constraint_matches_policy' => $manifestCompatible,
            'major_series' => 13,
        ];
    }

    private function manifestConstraint(): ?string
    {
        $path = base_path('composer.json');
        if (! is_file($path)) {
            return null;
        }

        try {
            $manifest = json_decode(
                (string) file_get_contents($path),
                true,
                128,
                JSON_THROW_ON_ERROR,
            );
        } catch (\Throwable) {
            return null;
        }

        $constraint = $manifest['require']['laravel/framework'] ?? null;

        return is_string($constraint) ? trim($constraint) : null;
    }

    /** @return array<string, mixed> */
    public function assertCompatible(): array
    {
        $status = $this->status();

        if ($status['status'] !== 'pass') {
            throw new RuntimeException(sprintf(
                'Laravel %s is outside Nexora\'s certified range %s <= version < %s.',
                $status['installed_version'],
                $status['minimum_version'],
                $status['maximum_exclusive'],
            ));
        }

        return $status;
    }
}
