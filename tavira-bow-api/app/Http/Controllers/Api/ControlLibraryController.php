<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ControlLibraryResource;
use App\Models\ControlLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ControlLibraryController extends Controller
{
    /**
     * List all controls
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ControlLibrary::query();

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('control_type')) {
            $query->where('control_type', $request->control_type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'ilike', "%{$search}%")
                    ->orWhere('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        $controls = $query->orderBy('code')->paginate(50);

        return ControlLibraryResource::collection($controls);
    }

    /**
     * Create new control
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:control_library,code',
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'control_type' => 'nullable|string|max:50',
            'frequency' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        $control = ControlLibrary::create($request->all());

        return response()->json([
            'message' => 'Control created successfully',
            'control' => new ControlLibraryResource($control),
        ], 201);
    }

    /**
     * Get single control
     */
    public function show(ControlLibrary $control): JsonResponse
    {
        $control->loadCount('riskControls');

        return response()->json([
            'control' => new ControlLibraryResource($control),
        ]);
    }

    /**
     * Update control
     */
    public function update(Request $request, ControlLibrary $control): JsonResponse
    {
        $request->validate([
            'code' => 'sometimes|string|max:50|unique:control_library,code,'.$control->id,
            'name' => 'sometimes|string|max:200',
            'description' => 'nullable|string',
            'control_type' => 'nullable|string|max:50',
            'frequency' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        $control->update($request->all());

        return response()->json([
            'message' => 'Control updated successfully',
            'control' => new ControlLibraryResource($control),
        ]);
    }

    /**
     * Delete control
     */
    public function destroy(ControlLibrary $control): JsonResponse
    {
        // Check if control is in use
        if ($control->riskControls()->exists()) {
            return response()->json([
                'message' => 'Cannot delete control that is assigned to risks',
            ], 422);
        }

        $control->delete();

        return response()->json([
            'message' => 'Control deleted successfully',
        ]);
    }

    /**
     * Get controls for dropdown
     */
    public function dropdown(): JsonResponse
    {
        $controls = ControlLibrary::active()
            ->select('id', 'code', 'name', 'control_type')
            ->orderBy('code')
            ->get();

        return response()->json([
            'controls' => $controls->map(fn ($c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
                'label' => "{$c->code} - {$c->name}",
                'type' => $c->control_type,
            ]),
        ]);
    }
}
