<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Display the public welcome page.
     */
    public function __invoke(): View
    {
        return view('welcome', [
            'latestRecipes' => Recipe::query()
                ->published()
                ->with('thumbnail')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(6)
                ->get(),
        ]);
    }
}
