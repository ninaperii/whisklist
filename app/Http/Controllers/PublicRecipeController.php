<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\View\View;

class PublicRecipeController extends Controller
{
    public function show(string $recipe): View
    {
        $publishedRecipe = Recipe::query()
            ->published()
            ->where('slug', $recipe)
            ->with(['author', 'categories', 'dietaryTags', 'ingredients', 'steps', 'thumbnail'])
            ->firstOrFail();

        return view('recipes.show', ['recipe' => $publishedRecipe]);
    }
}
