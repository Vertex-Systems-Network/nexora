<?php

declare(strict_types=1);

namespace App\Nexora\Cloud\Contracts;

interface ObjectStorageContract
{
    public function disk(): string;
    /** @return array{disk:string,driver:string,shared:bool,temporary_urls:bool,public_urls:bool} */
    public function capabilities(): array;
    public function put(string $path, string $contents, array $options = []): void;
    public function get(string $path): string;
    public function exists(string $path): bool;
    public function delete(string $path): void;
    public function url(string $path): ?string;
}
