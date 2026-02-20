<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WorkItemFileController extends Controller
{
    public function index(WorkItem $workitem): JsonResponse
    {
        $files = [];
        $path = "workitems/{$workitem->id}";

        if (Storage::disk()->exists($path)) {
            $allFiles = Storage::disk()->files($path);
            foreach ($allFiles as $file) {
                $files[] = [
                    'name' => basename($file),
                    'size' => Storage::disk()->size($file),
                    'url' => route('workitems.files.show', [$workitem->id, basename($file)]),
                ];
            }
        }

        return response()->json($files);
    }

    public function store(Request $request, WorkItem $workitem): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,csv,txt,png,jpg,jpeg,gif',
        ]);

        $file = $request->file('file');
        $path = $file->storeAs("workitems/{$workitem->id}", $file->getClientOriginalName());

        return response()->json([
            'name' => $file->getClientOriginalName(),
            'path' => $path,
        ], 201);
    }

    public function show(WorkItem $workitem, string $filename)
    {
        $path = "workitems/{$workitem->id}/{$filename}";

        if (! Storage::disk()->exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::disk()->download($path);
    }

    public function destroy(WorkItem $workitem, string $filename): JsonResponse
    {
        $path = "workitems/{$workitem->id}/{$filename}";

        if (Storage::disk()->exists($path)) {
            Storage::disk()->delete($path);
        }

        return response()->json(null, 204);
    }
}
