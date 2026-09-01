<?php

declare(strict_types=1);

namespace App\Nexora\Ai\Contracts;

use App\Nexora\Ai\Data\AiTextGenerationRequest;
use App\Nexora\Ai\Data\AiTextGenerationResult;

interface AiTextProviderContract
{
    public function key(): string;
    public function label(): string;

    /** @return array{ok:bool,message:string} */
    public function health(array $credentials, array $settings = []): array;

    public function generate(AiTextGenerationRequest $request): AiTextGenerationResult;
}
