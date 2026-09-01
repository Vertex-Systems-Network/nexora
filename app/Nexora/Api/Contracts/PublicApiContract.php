<?php

declare(strict_types=1);

namespace App\Nexora\Api\Contracts;

interface PublicApiContract
{
    public function version(): string;

    /** @return list<array{slug:string,label:string,description:string}> */
    public function abilities(): array;

    /** @return list<array{name:string,method:string,path:string,ability:string,pagination:?string,max_per_page:?int}> */
    public function resources(): array;
}
