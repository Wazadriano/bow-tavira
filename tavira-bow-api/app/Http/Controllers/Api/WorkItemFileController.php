<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class WorkItemFileController extends Controller
{
    public function index(WorkItem $workitem): JsonResponse
    {
        $files = [];
        $path = "workitems/{$workitem->id}";

        if (Storage::disk('local')->exists($path)) {
            $allFiles = Storage::disk('local')->files($path);
            foreach ($allFiles as $file) {
                $files[] = [
                    'name' => basename($file),
                    'size' => Storage::disk('local')->size($file),
                    'url' => route('workitems.files.show', [$workitem->id, basename($file)]),
                ];
            }
        }

        return response()->json($files);
    }

    public function store(Request $request, WorkItem $workitem): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->storeAs("workitems/{$workitem->id}", $file->getClientOriginalName(), 'local');

        return response()->json([
            'name' => $file->getClientOriginalName(),
            'path' => $path,
        ], 201);
    }

    public function show(WorkItem $workitem, string $filename)
    {
        $path = "workitems/{$workitem->id}/{$filename}";

        if (!Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::disk('local')->download($path);
    }

    public function destroy(WorkItem $workitem, string $filename): JsonResponse
    {
        $path = "workitems/{$workitem->id}/{$filename}";

        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }

        return response()->json(null, 204);
    }
}
