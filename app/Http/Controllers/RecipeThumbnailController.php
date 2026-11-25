<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecipeThumbnailController extends Controller
{
    public function __invoke(Recipe $recipe): StreamedResponse
    {
        abort_unless($recipe->isPublished() || Gate::allows('update', $recipe), 404);

        $thumbnail = $recipe->thumbnail;
        $storage = Storage::disk('local');
        abort_unless($thumbnail !== null && $storage->exists($thumbnail->path), 404);

        return $storage->response($thumbnail->path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
