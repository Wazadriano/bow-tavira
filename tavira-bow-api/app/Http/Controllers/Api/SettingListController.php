<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SettingList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingListController extends Controller
{
    /**
     * Get all setting lists
     */
    public function index(Request $request): JsonResponse
    {
        $query = SettingList::query();

        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        if ($request->has('is_active')) {
            if (filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN)) {
                $query->active();
            }
        }

        $settings = $query->ordered()->get();

        // Group by type
        $grouped = $settings->groupBy('type');

        return response()->json([
            'settings' => $grouped,
        ]);
    }

    /**
     * Get settings by type
     */
    public function byType(string $type): JsonResponse
    {
        $settings = SettingList::active()
            ->ofType($type)
            ->ordered()
            ->get();

        return response()->json([
            'type' => $type,
            'values' => $settings->map(fn($s) => [
                'id' => $s->id,
                'value' => $s->value,
                'label' => $s->label ?? $s->value,
            ]),
        ]);
    }

    /**
     * Store a new setting
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', SettingList::class);

        $request->validate([
            'type' => 'required|string|max:50',
            'value' => 'required|string|max:100',
            'label' => 'nullable|string|max:100',
            'order' => 'nullable|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $setting = SettingList::create([
            'type' => $request->type,
            'value' => $request->value,
            'label' => $request->label ?? $request->value,
            'order' => $request->order ?? SettingList::where('type', $request->type)->max('order') + 1,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'message' => 'Setting created successfully',
            'setting' => $setting,
        ], 201);
    }

    /**
     * Update a setting
     */
    public function update(Request $request, SettingList $setting): JsonResponse
    {
        $this->authorize('update', $setting);

        $request->validate([
            'value' => 'sometimes|string|max:100',
            'label' => 'nullable|string|max:100',
            'order' => 'nullable|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $setting->update($request->only(['value', 'label', 'order', 'is_active']));

        return response()->json([
            'message' => 'Setting updated successfully',
            'setting' => $setting,
        ]);
    }

    /**
     * Delete a setting
     */
    public function destroy(SettingList $setting): JsonResponse
    {
        $this->authorize('delete', $setting);

        $setting->delete();

        return response()->json([
            'message' => 'Setting deleted successfully',
        ]);
    }

    /**
     * Get departments list
     */
    public function departments(): JsonResponse
    {
        return response()->json([
            'departments' => SettingList::getDepartments(),
        ]);
    }

    /**
     * Get activities list
     */
    public function activities(): JsonResponse
    {
        return response()->json([
            'activities' => SettingList::getActivities(),
        ]);
    }

    /**
     * Get entities list
     */
    public function entities(): JsonResponse
    {
        return response()->json([
            'entities' => SettingList::getEntities(),
        ]);
    }

    /**
     * Reorder settings
     */
    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('update', SettingList::class);

        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:setting_lists,id',
            'items.*.order' => 'required|integer',
        ]);

        foreach ($request->items as $item) {
            SettingList::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json([
            'message' => 'Settings reordered successfully',
        ]);
    }
}
