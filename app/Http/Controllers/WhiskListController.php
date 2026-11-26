<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WhiskListController extends Controller
{
    public function index(Request $request): View
    {
        return view('recipes.saved', [
            'recipes' => $request->user()->savedRecipes()
                ->published()
                ->with(['author', 'thumbnail'])
                ->withAllergenWarnings($request->user())
                ->orderByPivotDesc('created_at')
                ->orderByDesc('recipes.id')
                ->paginate(12),
        ]);
    }

    public function store(Request $request, Recipe $recipe): RedirectResponse|JsonResponse
    {
        DB::transaction(function () use ($request, $recipe): void {
            $recipe = Recipe::query()->published()->lockForUpdate()->findOrFail($recipe->id);

            $savedRecipes = $request->user()->savedRecipes();
            if (! $savedRecipes->wherePivot('recipe_id', $recipe->id)->exists()) {
                $savedRecipes->attach($recipe->id, ['created_at' => now()]);
            }
        });

        return $this->afterChange($request, $recipe, true);
    }

    public function destroy(Request $request, Recipe $recipe): RedirectResponse|JsonResponse
    {
        $request->user()->savedRecipes()->detach($recipe->id);

        return $this->afterChange($request, $recipe, false);
    }

    private function afterChange(Request $request, Recipe $recipe, bool $saved): RedirectResponse|JsonResponse
    {
        $status = $saved ? 'Saved to My WhiskList.' : 'Removed from My WhiskList.';
        if ($request->expectsJson()) {
            return response()->json(['saved' => $saved, 'message' => $status]);
        }

        if ($request->input('from') === 'recipes') {
            $query = $request->input('return_search');
            $filters = [];
            if (is_string($query) && strlen($query) <= 16384) {
                parse_str($query, $filters);
            }

            return to_route('recipes.index', $filters)->with('status', $status);
        }

        if ($request->input('from') === 'whisklist' || ! $recipe->isPublished()) {
            return to_route('whisklist.index')->with('status', $status);
        }

        return to_route('recipes.show', $recipe)->with('status', $status);
    }
}
