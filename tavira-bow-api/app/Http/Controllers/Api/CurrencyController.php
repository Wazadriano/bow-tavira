<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * RG-BOW-013 - Conversion multi-devises GBP.
 * Exposes exchange rates for frontend display of amounts in GBP.
 */
class CurrencyController extends Controller
{
    public function rates(): JsonResponse
    {
        $ratesToGbp = config('currency.rates_to_gbp', ['EUR' => 0.85, 'USD' => 0.79, 'GBP' => 1.0]);

        return response()->json([
            'default_currency' => config('currency.default', 'GBP'),
            'rates_to_gbp' => $ratesToGbp,
        ]);
    }
}
