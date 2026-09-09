<?php

declare(strict_types=1);

namespace App\Nexora\Commerce\Services;

use App\Models\CommerceCurrency;
use App\Nexora\Foundation\Database\ConcurrencyGuard;
use InvalidArgumentException;

final class CurrencyManager
{
    public function __construct(private readonly ConcurrencyGuard $concurrency) {}

    public function normalize(string $currency): string
    {
        $code = strtoupper(trim($currency));
        if (preg_match('/^[A-Z]{3}$/', $code) !== 1) {
            throw new InvalidArgumentException('Currency must be a three-letter ISO-style code.');
        }

        return $code;
    }

    public function ensureEnabled(string $currency): CommerceCurrency
    {
        $code = $this->normalize($currency);
        $record = CommerceCurrency::query()->whereKey($code)->where('enabled', true)->first();
        if (! $record) {
            throw new InvalidArgumentException("Currency {$code} is not enabled for Commerce.");
        }

        return $record;
    }

    public function save(string $code, string $name, ?string $symbol, int $minorUnit, bool $enabled, bool $default): CommerceCurrency
    {
        $code = $this->normalize($code);
        if ($minorUnit < 0 || $minorUnit > 4) {
            throw new InvalidArgumentException('Currency minor unit must be between 0 and 4.');
        }

        return $this->concurrency->mutex('commerce.currency.default', function () use ($code, $name, $symbol, $minorUnit, $enabled, $default): CommerceCurrency {
            if ($default) {
                CommerceCurrency::query()->where('is_default', true)->update(['is_default' => false]);
            }

            $currency = CommerceCurrency::query()->updateOrCreate(['code' => $code], [
                'name' => trim($name),
                'symbol' => $symbol !== null ? trim($symbol) : null,
                'minor_unit' => $minorUnit,
                'enabled' => $enabled || $default,
                'is_default' => $default,
            ]);

            return $currency->refresh();
        });
    }

    public function toMinor(string $amount, string $currency): int
    {
        $record = $this->ensureEnabled($currency);
        $minor = (int) $record->minor_unit;
        $normalized = trim(str_replace([',', ' '], '', $amount));
        if (preg_match('/^\d+(?:\.\d+)?$/', $normalized) !== 1) {
            throw new InvalidArgumentException('Amount must be a positive decimal number.');
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');
        if (strlen($fraction) > $minor) {
            throw new InvalidArgumentException("Amount has more than {$minor} decimal places for {$record->code}.");
        }

        $whole = ltrim($whole, '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad($fraction, $minor, '0');
        $minorDigits = ltrim($whole.$fraction, '0');
        $minorDigits = $minorDigits === '' ? '0' : $minorDigits;
        $maximum = (string) PHP_INT_MAX;

        if (strlen($minorDigits) > strlen($maximum)
            || (strlen($minorDigits) === strlen($maximum) && strcmp($minorDigits, $maximum) > 0)) {
            throw new InvalidArgumentException('Amount exceeds the supported Commerce monetary range.');
        }

        return (int) $minorDigits;
    }

    public function defaultCode(): string
    {
        return (string) (CommerceCurrency::query()->where('is_default', true)->value('code')
            ?? CommerceCurrency::query()->where('enabled', true)->value('code')
            ?? 'USD');
    }

    public function format(int $amountMinor, string $currency): string
    {
        $record = CommerceCurrency::query()->find($this->normalize($currency));
        $minor = (int) ($record?->minor_unit ?? 2);
        $factor = 10 ** $minor;
        $number = number_format($amountMinor / $factor, $minor, '.', ',');

        return trim(($record?->symbol ?: $currency).' '.$number);
    }
}
