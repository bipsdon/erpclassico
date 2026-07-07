<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class OrderImageController extends Controller
{
    /**
     * Accept an image upload from the Quill editor, store it, and return
     * the public URL so Quill can embed it as a proper <img src="..."> tag
     * instead of a giant base64 blob.
     *
     * Route: POST /orders/editor-image
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp'],
        ]);

        $file       = $request->file('image');
        $name       = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path       = 'editor-images/' . $name;

        // Store in the public disk so the image is web-accessible via /storage/...
        Storage::disk('public')->putFileAs('editor-images', $file, $name);

        // Return a root-relative URL so it works regardless of APP_URL config
        return response()->json([
            'url' => '/storage/' . $path,
        ]);
    }
}
