<?php

use App\Services\CurrencyConversionService;

beforeEach(function () {
    $this->service = new CurrencyConversionService;
});

describe('toGbp', function () {

    it('converts EUR to GBP at 0.85 rate', function () {
        expect($this->service->toGbp(100, 'EUR'))->toBe(85.0);
    });

    it('converts USD to GBP at 0.79 rate', function () {
        expect($this->service->toGbp(100, 'USD'))->toBe(79.0);
    });

    it('returns same amount for GBP', function () {
        expect($this->service->toGbp(100, 'GBP'))->toBe(100.0);
    });

    it('falls back to rate 1.0 for unknown currency', function () {
        expect($this->service->toGbp(100, 'UNKNOWN'))->toBe(100.0);
    });
});

describe('fromGbp', function () {

    it('converts GBP to EUR', function () {
        expect($this->service->fromGbp(85, 'EUR'))->toBe(100.0);
    });

    it('converts GBP to USD', function () {
        $result = $this->service->fromGbp(79, 'USD');
        expect($result)->toBe(100.0);
    });

    it('returns same amount for GBP', function () {
        expect($this->service->fromGbp(100, 'GBP'))->toBe(100.0);
    });
});

describe('convert', function () {

    it('converts EUR to USD via GBP intermediary', function () {
        $result = $this->service->convert(100, 'EUR', 'USD');
        $expected = round(100 * 0.85 / 0.79, 2);
        expect($result)->toBe($expected);
    });

    it('returns same amount when source and target are identical', function () {
        expect($this->service->convert(100, 'EUR', 'EUR'))->toBe(100.0);
    });

    it('handles case-insensitive currency codes', function () {
        expect($this->service->convert(100, 'eur', 'eur'))->toBe(100.0);
    });
});

describe('metadata', function () {

    it('returns supported currencies including EUR USD GBP', function () {
        $currencies = $this->service->getSupportedCurrencies();
        expect($currencies)->toContain('EUR', 'USD', 'GBP');
    });

    it('returns GBP as default currency', function () {
        expect($this->service->getDefaultCurrency())->toBe('GBP');
    });

    it('returns rates array with expected keys', function () {
        $rates = $this->service->getRates();
        expect($rates)->toHaveKeys(['EUR', 'USD', 'GBP']);
    });
});
