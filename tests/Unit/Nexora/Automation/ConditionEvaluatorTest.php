<?php

declare(strict_types=1);

namespace Tests\Unit\Nexora\Automation;

use App\Nexora\Automation\Services\ConditionEvaluator;
use PHPUnit\Framework\TestCase;

final class ConditionEvaluatorTest extends TestCase
{
    public function test_it_evaluates_dotted_payload_conditions_without_executing_code(): void
    {
        $evaluator = new ConditionEvaluator();
        $context = ['document'=>['type'=>'article','status'=>'published','views'=>42,'title'=>'Nexora Architecture']];
        self::assertTrue($evaluator->passes([
            ['field'=>'document.type','operator'=>'equals','value'=>'article'],
            ['field'=>'document.title','operator'=>'contains','value'=>'architecture'],
            ['field'=>'document.views','operator'=>'greater_than','value'=>'10'],
        ], $context));
        self::assertFalse($evaluator->passes([['field'=>'document.status','operator'=>'equals','value'=>'draft']], $context));
    }
}
