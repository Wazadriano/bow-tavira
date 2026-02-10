<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default currency (display)
    |--------------------------------------------------------------------------
    */
    'default' => env('CURRENCY_DEFAULT', 'GBP'),

    /*
    |--------------------------------------------------------------------------
    | Exchange rates (RG-BOW-013 - Conversion multi-devises GBP)
    | Source currency => target (GBP) rate. 1 EUR = 0.85 GBP.
    |--------------------------------------------------------------------------
    */
    'rates_to_gbp' => [
        'EUR' => (float) env('CURRENCY_EUR_TO_GBP', 0.85),
        'USD' => (float) env('CURRENCY_USD_TO_GBP', 0.79),
        'GBP' => 1.0,
    ],
];
