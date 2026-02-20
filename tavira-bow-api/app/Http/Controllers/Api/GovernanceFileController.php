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
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,csv,txt,png,jpg,jpeg,gif',
        ]);

        $file = $request->file('file');
        $path = $file->storeAs("governance/{$item->id}", $file->getClientOriginalName());

        $existingMaxVersion = $item->attachments()
            ->where('original_filename', $file->getClientOriginalName())
            ->max('version');

        $version = $existingMaxVersion ? $existingMaxVersion + 1 : 1;

        $attachment = $item->attachments()->create([
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'version' => $version,
            'uploaded_by' => auth()->id(),
        ]);

        return response()->json($attachment, 201);
    }

    public function show(GovernanceItem $item, string $filename)
    {
        $path = "governance/{$item->id}/{$filename}";

        if (! Storage::disk()->exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::disk()->download($path);
    }

    public function destroy(GovernanceItem $item, string $filename): JsonResponse
    {
        $path = "governance/{$item->id}/{$filename}";

        if (Storage::disk()->exists($path)) {
            Storage::disk()->delete($path);
        }

        $item->attachments()->where('original_filename', $filename)->delete();

        return response()->json(null, 204);
    }
}
