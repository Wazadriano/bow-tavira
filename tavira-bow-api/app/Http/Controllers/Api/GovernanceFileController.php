<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GovernanceItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GovernanceFileController extends Controller
{
    public function index(GovernanceItem $item): JsonResponse
    {
        $files = $item->attachments()->get();

        return response()->json($files);
    }

    public function store(Request $request, GovernanceItem $item): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240',
            'version' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $path = $file->storeAs("governance/{$item->id}", $file->getClientOriginalName(), 'local');

        $attachment = $item->attachments()->create([
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'version' => $request->input('version', '1.0'),
            'uploaded_by' => auth()->id(),
        ]);

        return response()->json($attachment, 201);
    }

    public function show(GovernanceItem $item, string $filename)
    {
        $path = "governance/{$item->id}/{$filename}";

        if (! Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::disk('local')->download($path);
    }

    public function destroy(GovernanceItem $item, string $filename): JsonResponse
    {
        $path = "governance/{$item->id}/{$filename}";

        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }

        $item->attachments()->where('original_filename', $filename)->delete();

        return response()->json(null, 204);
    }
}
