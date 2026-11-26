<?php

namespace App\Http\Controllers;

use App\View\GuestHomeCache;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HomeController extends Controller
{
    /**
     * Display the public welcome page.
     */
    public function __invoke(Request $request, GuestHomeCache $cache): Response
    {
        if ($request->user() !== null) {
            return response()->view('welcome', ['latestRecipes' => $cache->latestRecipes($request->user())])
                ->header('X-WhiskList-Home-Cache', 'BYPASS');
        }

        $section = $cache->get();

        return response()->view('welcome', ['cachedRecipeSection' => $section['html']])
            ->header('X-WhiskList-Home-Cache', $section['status']);
    }
}
