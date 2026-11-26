<?php

namespace App\Http\Controllers;

use App\Enums\RecipeDifficulty;
use App\Enums\RecipeStatus;
use App\Http\Requests\SaveRecipeDraftRequest;
use App\Models\Category;
use App\Models\DietaryTag;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\View\GuestHomeCache;
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
    public function __construct(private GuestHomeCache $homeCache) {}

    public function index(Request $request): View
    {
        $draftsOnly = $request->query('status') === 'draft';

        return view('recipes.mine', [
            'recipes' => $request->user()->recipes()
                ->when($draftsOnly, fn ($query) => $query->draft())
                ->latest('updated_at')->orderByDesc('id')
                ->paginate(15)
                ->appends($draftsOnly ? ['status' => 'draft'] : []),
            'draftsOnly' => $draftsOnly,
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

    public function unpublish(Recipe $recipe): RedirectResponse
    {
        Gate::authorize('update', $recipe);

        DB::transaction(function () use ($recipe): void {
            $recipe = Recipe::query()->lockForUpdate()->findOrFail($recipe->id);
            $recipe->update(['status' => RecipeStatus::Draft, 'published_at' => null]);
        });
        rescue(fn () => $this->homeCache->warm());

        return to_route('recipes.mine')->with('status', 'Recipe is now private.');
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        Gate::authorize('delete', $recipe);

        $paths = DB::transaction(function () use ($recipe): array {
            $recipe = Recipe::query()->lockForUpdate()->findOrFail($recipe->id);
            $paths = $recipe->images()->pluck('path')->all();
            $recipe->images()->delete();
            $recipe->delete();

            return $paths;
        });

        Storage::disk('local')->delete($paths);
        rescue(fn () => $this->homeCache->warm());

        return to_route('recipes.mine')->with('status', 'Recipe deleted.');
    }

    private function persist(SaveRecipeDraftRequest $request, Recipe $recipe, string $draftMessage): RedirectResponse
    {
        $data = $request->validated();
        $publish = ($data['action'] ?? 'draft') === 'publish';
        $editorPath = $recipe->exists ? route('recipes.edit', $recipe, false) : route('recipes.create', absolute: false);
        $storage = Storage::disk('local');
        $newPath = null;
        $oldPath = null;
        $publishedNow = false;

        try {
            $recipe = DB::transaction(function () use ($recipe, $data, $publish, $storage, &$newPath, &$oldPath, &$publishedNow): Recipe {
                if ($recipe->exists) {
                    $recipe = Recipe::query()->lockForUpdate()->findOrFail($recipe->id);
                    Gate::authorize('update', $recipe);
                }

                $oldPath = $recipe->thumbnail?->path;
                if ($publish || $recipe->status === RecipeStatus::Published) {
                    $this->validatePublication($data, $oldPath !== null && $storage->exists($oldPath));
                }

                if (isset($data['thumbnail'])) {
                    $newPath = $data['thumbnail']->store('recipe-thumbnails', 'local');
                    if ($newPath === false) {
                        throw ValidationException::withMessages(['thumbnail' => 'The thumbnail could not be saved. Try uploading it again.']);
                    }
                }

                if ($publish && $recipe->status !== RecipeStatus::Published) {
                    $publishedNow = true;
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

        if ($recipe->isPublished()) {
            rescue(fn () => $this->homeCache->warm());
            $response = to_route('recipes.show', $recipe)->with('status', $publishedNow ? 'Recipe published.' : 'Recipe updated.');
            if ($publishedNow) {
                $response->with('published_from_editor', $editorPath);
            }

            return $response;
        }

        return to_route('recipes.edit', $recipe)->with('status', $draftMessage);
    }

    private function validatePublication(array $data, bool $hasThumbnail): void
    {
        $messages = [];

        foreach (['categories' => 'one category', 'ingredients' => 'one ingredient', 'steps' => 'one instruction step'] as $field => $requirement) {
            if (empty($data[$field])) {
                $messages[$field] = "A published recipe needs at least {$requirement}.";
            }
        }

        if (! $hasThumbnail && ! isset($data['thumbnail'])) {
            $messages['thumbnail'] = 'A published recipe needs a thumbnail.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
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
