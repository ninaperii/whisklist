<?php

namespace App\View;

use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class RecipeNavigation
{
    /**
     * @return array{label: string, url: string, parameters: array}
     */
    public static function origin(Request $request, Recipe $recipe): array
    {
        $source = $request->query('origin');
        $query = (array) $request->query('origin_query', []);
        [$route, $label, $queryKeys] = match ($source) {
            'home' => ['home', 'Home', []],
            'recipes' => ['recipes.index', 'All recipes', [
                'q', 'difficulty', 'max_time', 'categories', 'exclude_categories', 'ingredients',
                'require_all_ingredients', 'exclude_ingredients', 'dietary_tags', 'use_preferences',
                'skip_exclude_ingredients', 'skip_dietary_tags', 'page',
            ]],
            'profile' => ['users.show', 'Author profile', ['page']],
            'mine' => $request->user()?->id === $recipe->user_id ? ['recipes.mine', 'My recipes', ['page', 'status']] : [null, '', []],
            'saved' => $request->user() ? ['whisklist.index', 'My WhiskList', ['page']] : [null, '', []],
            'comments' => $request->user() ? ['comments.mine', 'My comments', ['page']] : [null, '', []],
            default => [null, '', []],
        };

        if ($route === null) {
            return $request->user()?->id === $recipe->user_id
                ? ['label' => 'My recipes', 'url' => route('recipes.mine'), 'parameters' => []]
                : ['label' => 'All recipes', 'url' => route('recipes.index'), 'parameters' => []];
        }

        $query = Arr::only($query, $queryKeys);
        $routeParameters = $route === 'users.show' ? ['user' => $recipe->user_id] + $query : $query;

        return [
            'label' => $label,
            'url' => route($route, $routeParameters),
            'parameters' => ['origin' => $source, 'origin_query' => $query],
        ];
    }
}
