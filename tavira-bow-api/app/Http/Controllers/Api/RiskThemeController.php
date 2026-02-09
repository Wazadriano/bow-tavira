<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RiskThemeResource;
use App\Models\RiskTheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RiskThemeController extends Controller
{
    /**
     * List all risk themes
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = RiskTheme::query()
            ->with(['categories'])
            ->withCount('categories');

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $themes = $query->ordered()->get();

        return RiskThemeResource::collection($themes);
    }

    /**
     * Create new theme
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:risk_themes,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'board_appetite' => 'nullable|integer|min:1|max:5',
            'order' => 'nullable|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $theme = RiskTheme::create([
            ...$request->all(),
            'order' => $request->order ?? RiskTheme::max('order') + 1,
        ]);

        return response()->json([
            'message' => 'Risk theme created successfully',
            'theme' => new RiskThemeResource($theme),
        ], 201);
    }

    /**
     * Get single theme
     */
    public function show(RiskTheme $theme): JsonResponse
    {
        $theme->load(['categories.risks', 'permissions.user']);

        return response()->json([
            'theme' => new RiskThemeResource($theme),
            'risk_count' => $theme->risk_count,
        ]);
    }

    /**
     * Update theme
     */
    public function update(Request $request, RiskTheme $theme): JsonResponse
    {
        $request->validate([
            'code' => 'sometimes|string|max:20|unique:risk_themes,code,'.$theme->id,
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'board_appetite' => 'nullable|integer|min:1|max:5',
            'order' => 'nullable|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $theme->update($request->all());

        return response()->json([
            'message' => 'Risk theme updated successfully',
            'theme' => new RiskThemeResource($theme),
        ]);
    }

    /**
     * Delete theme
     */
    public function destroy(RiskTheme $theme): JsonResponse
    {
        // Check if theme has categories/risks
        if ($theme->categories()->exists()) {
            return response()->json([
                'message' => 'Cannot delete theme with existing categories',
            ], 422);
        }

        $theme->delete();

        return response()->json([
            'message' => 'Risk theme deleted successfully',
        ]);
    }

    /**
     * Reorder themes
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:risk_themes,id',
            'items.*.order' => 'required|integer',
        ]);

        foreach ($request->items as $item) {
            RiskTheme::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json([
            'message' => 'Themes reordered successfully',
        ]);
    }
}
