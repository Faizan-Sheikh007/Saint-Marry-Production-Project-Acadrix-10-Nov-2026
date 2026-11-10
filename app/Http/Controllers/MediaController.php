<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /**
     * Serve a file from the "public" storage disk directly through Laravel,
     * instead of depending on the public/storage symlink (which is a common
     * source of broken images on Windows dev setups when the symlink was
     * never created, points at the wrong target, or requires elevated
     * permissions that weren't available when `php artisan storage:link` ran).
     */
    public function show(Request $request, string $path)
    {
        // Normalize and block path traversal (e.g. ../../.env)
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (str_contains($path, '..')) {
            abort(404);
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            abort(404);
        }

        return $disk->response($path, null, [
            'Cache-Control' => 'public, max-age=604800, immutable',
        ]);
    }
}
