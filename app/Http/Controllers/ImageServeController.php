<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class ImageServeController extends Controller
{
    /**
     * Serve an image from the specified disk (public or r2) and path.
     * Example: /serve-image/public/products/123/abc.jpg
     */
    public function serve($disk, $folder, $filename)
    {
        $path = $folder . '/' . $filename;
        try {
            if (!in_array($disk, ['public', 'r2'])) {
                abort(404, 'Invalid disk');
            }
            if (!Storage::disk($disk)->exists($path)) {
                abort(404, 'Image not found');
            }
            $file = Storage::disk($disk)->get($path);
            // Use PHP finfo for MIME type detection
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($file) ?: 'application/octet-stream';
            return Response::make($file, 200, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        } catch (\Throwable $e) {
            Log::error('Image serve error', [
                'disk' => $disk,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'Image server error');
        }
    }
}
