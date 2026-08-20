<?php

declare(strict_types=1);

namespace Tests\Unit\Commerce;

use App\Models\CommerceCurrency;
use App\Models\CommerceTaxRate;
use App\Nexora\Commerce\Services\CurrencyManager;
use App\Nexora\Commerce\Services\TaxCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CurrencyAndTaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_decimal_amounts_are_converted_to_minor_units_without_float_billing_math(): void
    {
        CommerceCurrency::query()->create(['code'=>'USD','name'=>'US Dollar','symbol'=>'$','minor_unit'=>2,'enabled'=>true,'is_default'=>true]);
        $manager=app(CurrencyManager::class);
        self::assertSame(12345,$manager->toMinor('123.45','usd'));
        self::assertSame('$ 123.45',$manager->format(12345,'USD'));
    }

    public function test_tax_calculator_supports_exclusive_and_inclusive_rules(): void
    {
        CommerceTaxRate::query()->create(['name'=>'Standard','rate_basis_points'=>2000,'inclusive'=>false,'active'=>true]);
        $exclusive=app(TaxCalculator::class)->calculate(10000);
        self::assertSame(2000,$exclusive['tax_minor']);
        self::assertSame(12000,$exclusive['total_minor']);

        CommerceTaxRate::query()->delete();
        CommerceTaxRate::query()->create(['name'=>'Included','rate_basis_points'=>2000,'inclusive'=>true,'active'=>true]);
        $inclusive=app(TaxCalculator::class)->calculate(12000);
        self::assertSame(2000,$inclusive['tax_minor']);
        self::assertSame(12000,$inclusive['total_minor']);
    }
}
