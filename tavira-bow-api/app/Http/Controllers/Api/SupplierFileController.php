<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupplierFileController extends Controller
{
    public function index(Supplier $supplier): JsonResponse
    {
        $files = $supplier->attachments()->get();

        return response()->json($files);
    }

    public function store(Request $request, Supplier $supplier): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240',
            'category' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $path = $file->storeAs("suppliers/{$supplier->id}", $file->getClientOriginalName(), 'local');

        $attachment = $supplier->attachments()->create([
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'category' => $request->input('category'),
            'uploaded_by' => auth()->id(),
        ]);

        return response()->json($attachment, 201);
    }

    public function destroy(Supplier $supplier, SupplierAttachment $file): JsonResponse
    {
        if (Storage::disk('local')->exists($file->stored_filename)) {
            Storage::disk('local')->delete($file->stored_filename);
        }

        $file->delete();

        return response()->json(null, 204);
    }
}
