<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Tavira BOW
|--------------------------------------------------------------------------
|
| This application is API-only. Web routes are minimal.
|
*/

Route::get('/', function () {
    return response()->json([
        'name' => 'Tavira BOW API',
        'version' => '1.0.0',
        'status' => 'running',
        'documentation' => '/api/documentation',
    ]);
});
