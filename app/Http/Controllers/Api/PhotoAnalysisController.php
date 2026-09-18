<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoAnalysisController extends Controller
{
    public function __construct(
        private readonly GeminiService $gemini,
    ) {}

    public function analyze(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'photo' => ['required', 'file', 'image', 'max:10240'],
        ]);

        $user = $request->user();
        $file = $request->file('photo');
        $directory = "photos/{$user->id}";
        $filename = time().'_'.$file->getClientOriginalName();
        $path = $file->storeAs($directory, $filename, 'local');

        if ($path === false) {
            abort(500, 'Failed to store photo.');
        }

        $photo = Photo::create([
            'user_id' => $user->id,
            'path' => $path,
            'mime' => $file->getMimeType(),
        ]);

        $absolutePath = Storage::disk('local')->path($path);
        $analysis = $this->gemini->analyzePhoto($absolutePath);

        return response()->json([
            'photo_id' => $photo->id,
            'analysis' => $analysis,
        ]);
    }
}
