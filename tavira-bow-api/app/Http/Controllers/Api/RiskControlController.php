<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RiskControlResource;
use App\Models\Risk;
use App\Models\RiskControl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RiskControlController extends Controller
{
    /**
     * List controls for a risk
     */
    public function index(Risk $risk): AnonymousResourceCollection
    {
        $this->authorize('view', $risk);

        $controls = $risk->controls()->with('control')->get();

        return RiskControlResource::collection($controls);
    }

    /**
     * Add control to risk
     */
    public function store(Request $request, Risk $risk): JsonResponse
    {
        $this->authorize('update', $risk);

        $request->validate([
            'control_id' => 'required|exists:control_library,id',
            'implementation_status' => 'nullable|string',
            'effectiveness_score' => 'nullable|integer|min:0|max:100',
            'notes' => 'nullable|string',
            'last_tested_date' => 'nullable|date',
            'next_test_date' => 'nullable|date',
        ]);

        // Check if control already exists for this risk
        if ($risk->controls()->where('control_id', $request->control_id)->exists()) {
            return response()->json([
                'message' => 'Control already assigned to this risk',
            ], 422);
        }

        $riskControl = RiskControl::create([
            'risk_id' => $risk->id,
            ...$request->all(),
        ]);

        // Recalculate risk scores
        $risk->calculateScores();
        $risk->save();

        $riskControl->load('control');

        return response()->json([
            'message' => 'Control added to risk',
            'risk_control' => new RiskControlResource($riskControl),
        ], 201);
    }

    /**
     * Update risk control
     */
    public function update(Request $request, Risk $risk, RiskControl $control): JsonResponse
    {
        $this->authorize('update', $risk);

        if ($control->risk_id !== $risk->id) {
            abort(404);
        }

        $request->validate([
            'implementation_status' => 'nullable|string',
            'effectiveness_score' => 'nullable|integer|min:0|max:100',
            'notes' => 'nullable|string',
            'last_tested_date' => 'nullable|date',
            'next_test_date' => 'nullable|date',
        ]);

        $control->update($request->all());

        // Recalculate risk scores
        $risk->calculateScores();
        $risk->save();

        $control->load('control');

        return response()->json([
            'message' => 'Risk control updated',
            'risk_control' => new RiskControlResource($control),
        ]);
    }

    /**
     * Remove control from risk
     */
    public function destroy(Risk $risk, RiskControl $control): JsonResponse
    {
        $this->authorize('update', $risk);

        if ($control->risk_id !== $risk->id) {
            abort(404);
        }

        $control->delete();

        // Recalculate risk scores
        $risk->calculateScores();
        $risk->save();

        return response()->json([
            'message' => 'Control removed from risk',
        ]);
    }
}
