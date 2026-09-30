<?php

declare(strict_types=1);

namespace App\Nexora\Commerce\Services;

use App\Models\CommerceTaxRate;
use InvalidArgumentException;
use OverflowException;

final class TaxCalculator
{
    /** @return array{tax_minor:int,total_minor:int,rate_basis_points:int,inclusive:bool,tax_rate_id:?string} */
    public function calculate(int $subtotalMinor, ?string $countryCode = null, ?string $regionCode = null, ?string $taxCode = null): array
    {
        if ($subtotalMinor < 0) {
            throw new InvalidArgumentException('Commerce tax subtotal cannot be negative.');
        }

        $query = CommerceTaxRate::query()->where('active', true);
        if ($taxCode !== null && $taxCode !== '') {
            $query->where(fn ($q) => $q->whereNull('tax_code')->orWhere('tax_code', $taxCode));
        }
        if ($countryCode !== null && $countryCode !== '') {
            $query->where(fn ($q) => $q->whereNull('country_code')->orWhere('country_code', strtoupper($countryCode)));
        }
        if ($regionCode !== null && $regionCode !== '') {
            $query->where(fn ($q) => $q->whereNull('region_code')->orWhere('region_code', $regionCode));
        }

        $rate = $query
            ->orderByRaw('CASE WHEN tax_code IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw('CASE WHEN region_code IS NULL THEN 1 ELSE 0 END')
            ->first();

        if (! $rate) {
            return ['tax_minor' => 0, 'total_minor' => $subtotalMinor, 'rate_basis_points' => 0, 'inclusive' => false, 'tax_rate_id' => null];
        }

        $basisPoints = (int) $rate->rate_basis_points;
        if ($basisPoints < 0 || $basisPoints > 10_000) {
            throw new InvalidArgumentException('Commerce tax rate must be between 0 and 100 percent.');
        }

        if ($rate->inclusive) {
            $denominator = 10_000 + $basisPoints;
            $tax = $this->scaledRounded($subtotalMinor, $basisPoints, $denominator);

            return [
                'tax_minor' => $tax,
                'total_minor' => $subtotalMinor,
                'rate_basis_points' => $basisPoints,
                'inclusive' => true,
                'tax_rate_id' => $rate->id,
            ];
        }

        $tax = $this->scaledRounded($subtotalMinor, $basisPoints, 10_000);
        if ($tax > PHP_INT_MAX - $subtotalMinor) {
            throw new OverflowException('Commerce tax calculation exceeds the supported monetary range.');
        }

        return [
            'tax_minor' => $tax,
            'total_minor' => $subtotalMinor + $tax,
            'rate_basis_points' => $basisPoints,
            'inclusive' => false,
            'tax_rate_id' => $rate->id,
        ];
    }

    private function scaledRounded(int $amount, int $numerator, int $denominator): int
    {
        if ($amount === 0 || $numerator === 0) {
            return 0;
        }

        $whole = intdiv($amount, $denominator);
        $remainder = $amount % $denominator;
        $wholeResult = $whole * $numerator;
        $remainderResult = (int) round(($remainder * $numerator) / $denominator);

        if ($remainderResult > PHP_INT_MAX - $wholeResult) {
            throw new OverflowException('Commerce tax calculation exceeds the supported monetary range.');
        }

        return $wholeResult + $remainderResult;
    }
}
