<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SageCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SageCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = SageCategory::orderBy('code')->get();
        return response()->json($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:sage_categories,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category = SageCategory::create($validated);
        return response()->json($category, 201);
    }

    public function show(SageCategory $sageCategory): JsonResponse
    {
        return response()->json($sageCategory);
    }

    public function update(Request $request, SageCategory $sageCategory): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'string|max:20|unique:sage_categories,code,' . $sageCategory->id,
            'name' => 'string|max:255',
            'description' => 'nullable|string',
        ]);

        $sageCategory->update($validated);
        return response()->json($sageCategory);
    }

    public function destroy(SageCategory $sageCategory): JsonResponse
    {
        $sageCategory->delete();
        return response()->json(null, 204);
    }
}
