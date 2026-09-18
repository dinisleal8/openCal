<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Services\FoodPhotoAnalyzer;
use App\Services\ImageNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoAnalysisController extends Controller
{
    public function __construct(
        private readonly FoodPhotoAnalyzer $analyzer,
    ) {}

    public function analyze(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'photo' => ['required', 'file', 'image', 'max:10240'],
        ]);

        $user = $request->user();
        $file = $request->file('photo');
        $disk = config('opencal.photos.disk', 'local');
        $directory = "photos/{$user->id}";
        $filename = time().'_'.$file->getClientOriginalName();
        $path = $file->storeAs($directory, $filename, $disk);

        if ($path === false) {
            abort(500, 'Failed to store photo.');
        }

        $photo = Photo::create([
            'user_id' => $user->id,
            'path' => $path,
            'mime' => $file->getMimeType(),
        ]);

        $absolutePath = Storage::disk($disk)->path($path);

        $normalized = ImageNormalizer::prepare($absolutePath);

        try {
            $analysis = $this->analyzer->analyzePhoto($normalized ?? $absolutePath);
        } finally {
            if ($normalized !== null) {
                @unlink($normalized);
            }
        }

        return response()->json([
            'photo_id' => $photo->id,
            'analysis' => $analysis,
        ]);
    }
}
