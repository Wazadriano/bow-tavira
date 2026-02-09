<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RiskCategoryResource;
use App\Models\RiskCategory;
use App\Models\RiskTheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RiskCategoryController extends Controller
{
    /**
     * List categories for a theme
     */
    public function index(Request $request, RiskTheme $theme): AnonymousResourceCollection
    {
        $query = $theme->categories()
            ->withCount('risks');

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $categories = $query->ordered()->get();

        return RiskCategoryResource::collection($categories);
    }

    /**
     * Create new category
     */
    public function store(Request $request, RiskTheme $theme): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        // Check unique code within theme
        if ($theme->categories()->where('code', $request->code)->exists()) {
            return response()->json([
                'message' => 'Category code already exists in this theme',
            ], 422);
        }

        $category = RiskCategory::create([
            'theme_id' => $theme->id,
            ...$request->all(),
            'order' => $request->order ?? $theme->categories()->max('order') + 1,
        ]);

        return response()->json([
            'message' => 'Risk category created successfully',
            'category' => new RiskCategoryResource($category),
        ], 201);
    }

    /**
     * Get single category
     */
    public function show(RiskTheme $theme, RiskCategory $category): JsonResponse
    {
        if ($category->theme_id !== $theme->id) {
            abort(404);
        }

        $category->load(['risks', 'theme']);

        return response()->json([
            'category' => new RiskCategoryResource($category),
        ]);
    }

    /**
     * Update category
     */
    public function update(Request $request, RiskTheme $theme, RiskCategory $category): JsonResponse
    {
        if ($category->theme_id !== $theme->id) {
            abort(404);
        }

        $request->validate([
            'code' => 'sometimes|string|max:20',
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        // Check unique code within theme
        if ($request->has('code') && $request->code !== $category->code) {
            if ($theme->categories()->where('code', $request->code)->exists()) {
                return response()->json([
                    'message' => 'Category code already exists in this theme',
                ], 422);
            }
        }

        $category->update($request->all());

        return response()->json([
            'message' => 'Risk category updated successfully',
            'category' => new RiskCategoryResource($category),
        ]);
    }

    /**
     * Delete category
     */
    public function destroy(RiskTheme $theme, RiskCategory $category): JsonResponse
    {
        if ($category->theme_id !== $theme->id) {
            abort(404);
        }

        // Check if category has risks
        if ($category->risks()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category with existing risks',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Risk category deleted successfully',
        ]);
    }
}
