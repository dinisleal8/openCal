<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhotoController extends Controller
{
    public function show(Photo $photo): StreamedResponse
    {
        Gate::authorize('view', $photo);

        $disk = config('opencal.photos.disk', 'local');

        abort_unless(Storage::disk($disk)->exists($photo->path), 404);

        return Storage::disk($disk)->response($photo->path, null, [
            'Content-Type' => $photo->mime,
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
