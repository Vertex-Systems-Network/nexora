<?php

declare(strict_types=1);

namespace Tests\Unit\Studio;

use App\Nexora\Studio\Data\StudioElementDefinition;
use App\Nexora\Studio\Services\StudioBindingRegistry;
use App\Nexora\Studio\Services\StudioCanvasValidator;
use App\Nexora\Studio\Services\StudioElementRegistry;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class StudioCanvasValidatorTest extends TestCase
{
    public function test_it_accepts_registered_elements_and_allow_listed_bindings(): void
    {
        $elements = new StudioElementRegistry();
        $elements->register(new StudioElementDefinition('heading', 'Heading', 'Content', 'heading', false, ['text' => 'Hello'], [], ['text']));
        $bindings = new StudioBindingRegistry();
        $bindings->register('document.title', 'Document title', 'Document');
        $validator = new StudioCanvasValidator($elements, $bindings);

        $content = $validator->validate(['version' => 1, 'children' => [[
            'id' => 'heading_12345678', 'type' => 'heading', 'props' => ['text' => 'Fallback', 'level' => 2],
            'styles' => ['base' => ['fontSize' => '36px'], 'tablet' => [], 'mobile' => []],
            'bindings' => ['text' => 'document.title'], 'children' => [],
        ]]]);

        self::assertSame('document.title', $content['children'][0]['bindings']['text']);
        self::assertSame('36px', $content['children'][0]['styles']['base']['fontSize']);
    }

    public function test_it_rejects_unknown_elements(): void
    {
        $this->expectException(ValidationException::class);
        $validator = new StudioCanvasValidator(new StudioElementRegistry(), new StudioBindingRegistry());
        $validator->validate(['version' => 1, 'children' => [['id' => 'unknown_12345678', 'type' => 'unknown']]]);
    }
}
