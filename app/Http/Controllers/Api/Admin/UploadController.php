<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $path = $request->file('image')->store('menus', 'public');

        return response()->json([
            'message' => 'Gambar berhasil diunggah.',
            'url' => url(Storage::disk('public')->url($path)),
            'path' => $path,
        ], 201);
    }
}
