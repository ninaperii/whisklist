<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Display the public welcome page.
     */
    public function __invoke(Request $request): View
    {
        return view('welcome', [
            'latestRecipes' => Recipe::query()
                ->published()
                ->with('thumbnail')
                ->withAllergenWarnings($request->user())
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(6)
                ->get(),
        ]);
    }
}
