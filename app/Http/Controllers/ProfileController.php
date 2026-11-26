<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\Allergen;
use App\Models\DietaryTag;
use App\Models\Ingredient;
use App\Models\User;
use App\View\GuestHomeCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display public attribution and recipes, with profile details when permitted.
     */
    public function show(Request $request, User $user): View
    {
        return view('profiles.show', [
            'profile' => $user,
            'showDetails' => Gate::allows('view', $user),
            'recipes' => $user->recipes()->published()->with(['author', 'thumbnail'])
                ->withAllergenWarnings($request->user())
                ->orderByDesc('published_at')->orderByDesc('id')->paginate(12),
        ]);
    }

    /**
     * Display the authenticated user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);
        Gate::authorize('update', $user);

        return view('profiles.edit', [
            'user' => $user,
            'dietaryTagOptions' => DietaryTag::query()->orderBy('name')->pluck('name', 'id'),
            'ingredientOptions' => Ingredient::query()->orderBy('name')->pluck('name', 'id'),
            'allergenOptions' => Allergen::query()->orderBy('name')->pluck('name', 'id'),
            'preferredTags' => $user->preferredDietaryTags()->allRelatedIds()->all(),
            'excludedIngredients' => $user->excludedIngredients()->allRelatedIds()->all(),
            'selectedAllergens' => $user->allergens()->allRelatedIds()->all(),
            'returnSearch' => $request->query('return_search'),
            'returnToRecipes' => $this->recipeSearchUrl($request->query('return_search')),
        ]);
    }

    public function updateRecipePreferences(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $preferences = $request->validateWithBag('recipePreferences', [
            'dietary_tags' => ['sometimes', 'array', 'max:50'],
            'dietary_tags.*' => ['required', 'integer', 'distinct', 'exists:dietary_tags,id'],
            'exclude_ingredients' => ['sometimes', 'array', 'max:50'],
            'exclude_ingredients.*' => ['required', 'integer', 'distinct', 'exists:ingredients,id'],
            'allergens' => ['sometimes', 'array', 'max:50'],
            'allergens.*' => ['required', 'integer', 'distinct', 'exists:allergens,id'],
        ]);

        DB::transaction(function () use ($user, $preferences): void {
            User::query()->lockForUpdate()->findOrFail($user->id);
            $user->preferredDietaryTags()->sync($preferences['dietary_tags'] ?? []);
            $user->excludedIngredients()->sync($preferences['exclude_ingredients'] ?? []);
            $user->allergens()->sync($preferences['allergens'] ?? []);
        });

        return redirect($this->recipeSearchUrl($request->input('return_search')) ?? route('profile.edit').'#recipe-preferences')
            ->with('recipe_preference_status', 'Recipe preferences saved.');
    }

    private function recipeSearchUrl(mixed $search): ?string
    {
        if (! is_string($search) || ! str_starts_with($search, '?') || strlen($search) > 16384) {
            return null;
        }

        parse_str(substr($search, 1), $filters);

        return route('recipes.index', $filters);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(UpdateProfileRequest $request, GuestHomeCache $homeCache): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);
        Gate::authorize('update', $user);

        $user->update($request->validated());
        if ($user->wasChanged('name') && $user->recipes()->published()->exists()) {
            rescue(fn () => $homeCache->warm());
        }

        return redirect()
            ->route('profile.edit')
            ->with('status', 'Profile updated successfully.');
    }
}
