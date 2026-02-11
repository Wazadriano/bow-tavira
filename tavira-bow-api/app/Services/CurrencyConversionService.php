<?php

namespace App\Services;

class CurrencyConversionService
{
    private array $ratesToGbp;

    private string $defaultCurrency;

    public function __construct()
    {
        $this->ratesToGbp = config('currency.rates_to_gbp', [
            'EUR' => 0.85,
            'USD' => 0.79,
            'GBP' => 1.0,
        ]);
        $this->defaultCurrency = config('currency.default', 'GBP');
    }

    public function toGbp(float $amount, string $fromCurrency): float
    {
        $rate = $this->ratesToGbp[strtoupper($fromCurrency)] ?? 1.0;

        return round($amount * $rate, 2);
    }

    public function fromGbp(float $amountGbp, string $toCurrency): float
    {
        $rate = $this->ratesToGbp[strtoupper($toCurrency)] ?? 1.0;

        if ($rate === 0.0) {
            return 0;
        }

        return round($amountGbp / $rate, 2);
    }

    public function convert(float $amount, string $from, string $to): float
    {
        if (strtoupper($from) === strtoupper($to)) {
            return $amount;
        }

        $gbpAmount = $this->toGbp($amount, $from);

        return $this->fromGbp($gbpAmount, $to);
    }

    public function getRates(): array
    {
        return $this->ratesToGbp;
    }

    public function getDefaultCurrency(): string
    {
        return $this->defaultCurrency;
    }

    public function getSupportedCurrencies(): array
    {
        return array_keys($this->ratesToGbp);
    }
}
