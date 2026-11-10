<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Handles direct profile photo uploads for orders.
 *
 * The file is resized to fit within 300×300 px (maintaining aspect ratio)
 * using the GD extension (always available), then stored on the public disk
 * so it can be served as a regular <img src="..."> URL.
 *
 * The original filename is discarded; a UUID is used instead.
 *
 * Route: POST /orders/profile-photo
 */
class OrderProfilePhotoController extends Controller
{
    private const MAX_DIMENSION = 300;   // pixels
    private const JPEG_QUALITY  = 88;    // 0-100
    private const STORAGE_DIR   = 'profile-photos';
    private const MAX_FILE_SIZE = 5120;  // kb — 5 MB

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->isPipelineManager(), 403);

        $request->validate([
            'photo' => [
                'required',
                'file',
                'image',
                'max:' . self::MAX_FILE_SIZE,
                'mimes:jpg,jpeg,png,gif,webp',
            ],
        ], [
            'photo.max'   => 'Profile photo must be under 5 MB.',
            'photo.mimes' => 'Only JPG, PNG, GIF, and WEBP images are accepted.',
        ]);

        $file = $request->file('photo');

        // ── Read & resize via GD ──────────────────────────────
        $resized = $this->resizeImage($file->getRealPath(), $file->getMimeType());

        // ── Store as JPEG on public disk ──────────────────────
        $filename  = Str::uuid() . '.jpg';
        $storagePath = self::STORAGE_DIR . '/' . $filename;

        Storage::disk('public')->put($storagePath, $resized);

        return response()->json([
            'url'  => '/storage/' . $storagePath,
            'path' => $storagePath,       // returned so the form can store it
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // GD resize helper
    // ──────────────────────────────────────────────────────────

    /**
     * Resize an image so that neither dimension exceeds MAX_DIMENSION,
     * preserving aspect ratio. Returns raw JPEG bytes.
     *
     * @throws \RuntimeException  if the image cannot be loaded
     */
    private function resizeImage(string $path, string $mime): string
    {
        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png'  => @imagecreatefrompng($path),
            'image/gif'  => @imagecreatefromgif($path),
            'image/webp' => @imagecreatefromwebp($path),
            default      => @imagecreatefromjpeg($path), // fallback
        };

        if (!$src) {
            throw new \RuntimeException('Could not load image for resizing.');
        }

        $origW = imagesx($src);
        $origH = imagesy($src);

        // Calculate new dimensions, capping at MAX_DIMENSION
        $maxDim = self::MAX_DIMENSION;
        if ($origW <= $maxDim && $origH <= $maxDim) {
            // Already small enough — no resize needed
            $newW = $origW;
            $newH = $origH;
        } elseif ($origW >= $origH) {
            $newW = $maxDim;
            $newH = (int) round($origH * $maxDim / $origW);
        } else {
            $newH = $maxDim;
            $newW = (int) round($origW * $maxDim / $origH);
        }

        $dst = imagecreatetruecolor($newW, $newH);

        // Preserve transparency for PNG/GIF sources
        if (in_array($mime, ['image/png', 'image/gif'], true)) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
            imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);

        // Capture JPEG output into a string
        ob_start();
        imagejpeg($dst, null, self::JPEG_QUALITY);
        $jpeg = ob_get_clean();
        imagedestroy($dst);

        return $jpeg;
    }
}
