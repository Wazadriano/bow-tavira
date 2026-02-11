<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Risk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RiskFileController extends Controller
{
    public function index(Risk $risk): JsonResponse
    {
        $files = $risk->attachments()->get();

        return response()->json($files);
    }

    public function store(Request $request, Risk $risk): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->storeAs("risks/{$risk->id}", $file->getClientOriginalName());

        $existingMaxVersion = $risk->attachments()
            ->where('original_filename', $file->getClientOriginalName())
            ->max('version');

        $version = $existingMaxVersion ? $existingMaxVersion + 1 : 1;

        $attachment = $risk->attachments()->create([
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'version' => $version,
            'uploaded_by' => auth()->id(),
        ]);

        return response()->json($attachment, 201);
    }

    public function show(Risk $risk, string $filename)
    {
        $path = "risks/{$risk->id}/{$filename}";

        if (! Storage::disk()->exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::disk()->download($path);
    }

    public function destroy(Risk $risk, string $filename): JsonResponse
    {
        $path = "risks/{$risk->id}/{$filename}";

        if (Storage::disk()->exists($path)) {
            Storage::disk()->delete($path);
        }

        $risk->attachments()->where('original_filename', $filename)->delete();

        return response()->json(null, 204);
    }

    /**
     * Download a risk attachment by id (for UI that uses file id).
     */
    public function showById(Risk $risk, int $id)
    {
        $attachment = $risk->attachments()->findOrFail($id);
        $path = $attachment->getAttribute('path')
            ?? $attachment->getAttribute('file_path')
            ?? $attachment->getAttribute('stored_filename')
            ?? "risks/{$risk->id}/".($attachment->getAttribute('original_filename') ?? $attachment->getAttribute('original_name') ?? $attachment->filename);

        if (! Storage::disk()->exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::disk()->download(
            $path,
            $attachment->original_filename ?? $attachment->original_name ?? $attachment->filename ?? 'file'
        );
    }

    /**
     * Delete a risk attachment by id.
     */
    public function destroyById(Risk $risk, int $id): JsonResponse
    {
        $attachment = $risk->attachments()->findOrFail($id);
        $path = $attachment->getAttribute('path')
            ?? $attachment->getAttribute('file_path')
            ?? $attachment->getAttribute('stored_filename')
            ?? "risks/{$risk->id}/".($attachment->getAttribute('original_filename') ?? $attachment->getAttribute('original_name') ?? $attachment->filename);

        if (Storage::disk()->exists($path)) {
            Storage::disk()->delete($path);
        }

        $attachment->delete();

        return response()->json(null, 204);
    }
}
