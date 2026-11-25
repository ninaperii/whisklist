<?php

namespace App\Http\Controllers;

use App\Enums\RecipeDifficulty;
use App\Http\Requests\SaveRecipeDraftRequest;
use App\Models\Category;
use App\Models\DietaryTag;
use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RecipeDraftController extends Controller
{
    public function index(Request $request): View
    {
        return view('recipes.mine', [
            'drafts' => $request->user()->recipes()->draft()->latest('updated_at')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Recipe);
    }

    public function store(SaveRecipeDraftRequest $request): RedirectResponse
    {
        $recipe = $request->user()->recipes()->make();
        DB::transaction(fn () => $this->save($recipe, $request->validated()));

        return to_route('recipes.edit', $recipe)->with('status', 'Draft saved.');
    }

    public function edit(Recipe $recipe): View
    {
        Gate::authorize('update', $recipe);

        return $this->form($recipe);
    }

    public function update(SaveRecipeDraftRequest $request, Recipe $recipe): RedirectResponse
    {
        $recipe = DB::transaction(function () use ($recipe, $request): Recipe {
            $lockedRecipe = Recipe::query()->lockForUpdate()->findOrFail($recipe->id);
            Gate::authorize('update', $lockedRecipe);
            $this->save($lockedRecipe, $request->validated());

            return $lockedRecipe;
        });

        return to_route('recipes.edit', $recipe)->with('status', 'Draft updated.');
    }

    private function form(Recipe $recipe): View
    {
        return view('recipes.draft', [
            'recipe' => $recipe,
            'categories' => Category::query()->orderBy('name')->get(),
            'dietaryTags' => DietaryTag::query()->orderBy('name')->get(),
            'ingredients' => Ingredient::query()->orderBy('name')->get(),
            'difficulties' => RecipeDifficulty::cases(),
        ]);
    }

    private function save(Recipe $recipe, array $data): void
    {
        $recipe->fill(Arr::only($data, ['title', 'preparation_minutes', 'cooking_minutes', 'servings', 'difficulty']));
        $recipe->summary = $data['summary'] ?? '';
        $recipe->save();

        $recipe->categories()->sync($data['categories'] ?? []);
        $recipe->dietaryTags()->sync($data['dietary_tags'] ?? []);

        $ingredients = [];
        foreach (array_values($data['ingredients'] ?? []) as $index => $ingredient) {
            $ingredients[$ingredient['id']] = [
                'quantity' => $ingredient['quantity'] ?? null,
                'unit' => $ingredient['unit'] ?? null,
                'notes' => $ingredient['notes'] ?? null,
                'is_main' => $ingredient['is_main'] ?? false,
                'position' => $index + 1,
            ];
        }

        // Replace pivot rows together so reordering cannot collide with old positions.
        $recipe->ingredients()->detach();
        $recipe->ingredients()->attach($ingredients);

        $steps = array_values($data['steps'] ?? []);
        foreach ($steps as $index => $instruction) {
            $recipe->steps()->updateOrCreate(['position' => $index + 1], ['instruction' => $instruction]);
        }
        $recipe->steps()->where('position', '>', count($steps))->delete();
    }
}
