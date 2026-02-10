<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Uses Laravel's TestCase as the base for all tests.
| RefreshDatabase resets the DB between each test for isolation.
|
*/

uses(
    Tests\TestCase::class,
    RefreshDatabase::class,
)->in('Feature');

/*
|--------------------------------------------------------------------------
| Ensure storage directories exist (CI fix)
|--------------------------------------------------------------------------
|
| In CI environments the storage/framework directories may not exist,
| causing "Please provide a valid cache path" errors.
|
*/

beforeEach(function () {
    $dirs = [
        storage_path('framework/cache/data'),
        storage_path('framework/views'),
        storage_path('framework/sessions'),
    ];
    foreach ($dirs as $dir) {
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
})->in('Feature');

uses(
    Tests\TestCase::class,
)->in('Unit');
