<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = SystemSetting::pluck('value', 'key');

        return response()->json($settings);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'required',
        ]);

        $setting = SystemSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $validated['value']]
        );

        return response()->json($setting);
    }
}
