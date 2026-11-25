<?php

namespace App\Http\Controllers;

use App\Enums\RecipeDifficulty;
use App\Enums\RecipeStatus;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

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
        return $this->persist($request, $request->user()->recipes()->make(), 'Draft saved.');
    }

    public function edit(Recipe $recipe): View
    {
        Gate::authorize('update', $recipe);

        return $this->form($recipe);
    }

    public function update(SaveRecipeDraftRequest $request, Recipe $recipe): RedirectResponse
    {
        return $this->persist($request, $recipe, 'Draft updated.');
    }

    private function persist(SaveRecipeDraftRequest $request, Recipe $recipe, string $draftMessage): RedirectResponse
    {
        $data = $request->validated();
        $publish = ($data['action'] ?? 'draft') === 'publish';
        $storage = Storage::disk('local');
        $newPath = null;
        $oldPath = null;

        try {
            $recipe = DB::transaction(function () use ($recipe, $data, $publish, $storage, &$newPath, &$oldPath): Recipe {
                if ($recipe->exists) {
                    $recipe = Recipe::query()->lockForUpdate()->findOrFail($recipe->id);
                    Gate::authorize('update', $recipe);
                }

                $oldPath = $recipe->thumbnail?->path;
                if (isset($data['thumbnail'])) {
                    $newPath = $data['thumbnail']->store('recipe-thumbnails', 'local');
                    if ($newPath === false) {
                        throw ValidationException::withMessages(['thumbnail' => 'The thumbnail could not be saved. Try uploading it again.']);
                    }
                }

                if ($publish && $newPath === null && ($oldPath === null || ! $storage->exists($oldPath))) {
                    throw ValidationException::withMessages(['thumbnail' => 'Add a thumbnail before publishing.']);
                }

                if ($publish) {
                    $recipe->status = RecipeStatus::Published;
                    $recipe->published_at = now();
                    if ($recipe->slug === 'create') {
                        $recipe->slug = null;
                    }
                }

                $this->save($recipe, $data);

                if ($newPath !== null) {
                    $recipe->thumbnail()->updateOrCreate([], ['path' => $newPath, 'is_thumbnail' => true]);
                }

                return $recipe;
            });
        } catch (Throwable $exception) {
            if (is_string($newPath)) {
                $storage->delete($newPath);
            }

            throw $exception;
        }

        if ($newPath !== null && $oldPath !== null && $newPath !== $oldPath) {
            $storage->delete($oldPath);
        }

        if ($publish) {
            return to_route('recipes.show', $recipe)->with('status', 'Recipe published.');
        }

        return to_route('recipes.edit', $recipe)->with('status', $draftMessage);
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
