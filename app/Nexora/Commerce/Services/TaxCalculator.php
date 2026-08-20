<?php

declare(strict_types=1);

namespace App\Nexora\Commerce\Services;

use App\Models\CommerceTaxRate;

final class TaxCalculator
{
    /** @return array{tax_minor:int,total_minor:int,rate_basis_points:int,inclusive:bool,tax_rate_id:?string} */
    public function calculate(int $subtotalMinor, ?string $countryCode = null, ?string $regionCode = null, ?string $taxCode = null): array
    {
        $query = CommerceTaxRate::query()->where('active', true);
        if ($taxCode !== null && $taxCode !== '') $query->where(fn($q)=>$q->whereNull('tax_code')->orWhere('tax_code', $taxCode));
        if ($countryCode !== null && $countryCode !== '') $query->where(fn($q)=>$q->whereNull('country_code')->orWhere('country_code', strtoupper($countryCode)));
        if ($regionCode !== null && $regionCode !== '') $query->where(fn($q)=>$q->whereNull('region_code')->orWhere('region_code', $regionCode));
        $rate = $query->orderByRaw('CASE WHEN tax_code IS NULL THEN 1 ELSE 0 END')->orderByRaw('CASE WHEN region_code IS NULL THEN 1 ELSE 0 END')->first();
        if (! $rate) return ['tax_minor'=>0,'total_minor'=>$subtotalMinor,'rate_basis_points'=>0,'inclusive'=>false,'tax_rate_id'=>null];
        $basisPoints=(int)$rate->rate_basis_points;
        if ($rate->inclusive) {
            $tax = $basisPoints > 0 ? (int) round($subtotalMinor - ($subtotalMinor * 10000 / (10000 + $basisPoints))) : 0;
            return ['tax_minor'=>$tax,'total_minor'=>$subtotalMinor,'rate_basis_points'=>$basisPoints,'inclusive'=>true,'tax_rate_id'=>$rate->id];
        }
        $tax=(int) round($subtotalMinor * $basisPoints / 10000);
        return ['tax_minor'=>$tax,'total_minor'=>$subtotalMinor+$tax,'rate_basis_points'=>$basisPoints,'inclusive'=>false,'tax_rate_id'=>$rate->id];
    }
}
