<?php

namespace App\Http\Controllers;

use App\Enums\RecipeDifficulty;
use App\Models\Category;
use App\Models\Comment;
use App\Models\DietaryTag;
use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicRecipeController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $validator = Validator::make($request->query(), [
            'q' => ['nullable', 'string', 'max:120'],
            'use_preferences' => ['sometimes', 'boolean'],
            'difficulty' => ['sometimes', 'array', 'max:3'],
            'difficulty.*' => ['required', 'distinct', Rule::enum(RecipeDifficulty::class)],
            'max_time' => ['nullable', Rule::in([20, 30, 45])],
            'skip_exclude_ingredients' => ['sometimes', 'array', 'max:50'],
            'skip_exclude_ingredients.*' => ['required', 'integer', 'distinct'],
            'skip_dietary_tags' => ['sometimes', 'array', 'max:50'],
            'skip_dietary_tags.*' => ['required', 'integer', 'distinct'],
            'categories' => ['sometimes', 'array', 'max:100'],
            'categories.*' => ['required', 'integer', 'distinct', 'exists:categories,id'],
            'exclude_categories' => ['sometimes', 'array', 'max:100'],
            'exclude_categories.*' => ['required', 'integer', 'distinct', 'exists:categories,id'],
            'ingredients' => ['sometimes', 'array', 'max:50'],
            'require_all_ingredients' => ['sometimes', 'boolean'],
            'ingredients.*' => ['required', 'integer', 'distinct', 'exists:ingredients,id'],
            'exclude_ingredients' => ['sometimes', 'array', 'max:50'],
            'exclude_ingredients.*' => ['required', 'integer', 'distinct', 'exists:ingredients,id'],
            'dietary_tags' => ['sometimes', 'array', 'max:50'],
            'dietary_tags.*' => ['required', 'integer', 'distinct', 'exists:dietary_tags,id'],
        ]);

        if ($validator->fails()) {
            return to_route('recipes.index')->withErrors($validator);
        }

        $filters = array_filter($validator->validated(), fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
        if (empty($filters['ingredients']) || empty($filters['require_all_ingredients'])) {
            unset($filters['require_all_ingredients']);
        }
        $categoryTree = Category::query()->orderBy('name')->get()->groupBy('parent_id');
        $excluded = $this->expandCategories($categoryTree, $filters['exclude_categories'] ?? []);
        if (array_intersect($filters['categories'] ?? [], $excluded) !== []) {
            return to_route('recipes.index')->withErrors([
                'category_conflict' => 'A category cannot be included when it or one of its parents is excluded.',
            ]);
        }

        if (array_intersect($filters['ingredients'] ?? [], $filters['exclude_ingredients'] ?? []) !== []) {
            return to_route('recipes.index')->withErrors([
                'ingredient_conflict' => 'Choose either Include or Exclude for each ingredient.',
            ]);
        }

        $query = Recipe::query()->published()->with(['thumbnail', 'author']);
        $user = $request->user();
        if ($user !== null) {
            $query->withExists(['savedBy as is_saved' => fn (Builder $savedBy): Builder => $savedBy->whereKey($user->id)]);
        }
        $query->withAllergenWarnings($user);
        $usePreferences = $user !== null && (bool) ($filters['use_preferences'] ?? false);
        if (! $usePreferences) {
            unset($filters['use_preferences'], $filters['skip_exclude_ingredients'], $filters['skip_dietary_tags']);
        }

        $savedPreferences = [
            'exclude_ingredients' => $user?->excludedIngredients()->allRelatedIds()->all() ?? [],
            'dietary_tags' => $user?->preferredDietaryTags()->allRelatedIds()->all() ?? [],
        ];
        $effectiveFilters = $filters;
        $appliedPreferences = [];
        foreach ($savedPreferences as $field => $ids) {
            $manual = $filters[$field] ?? [];
            $appliedPreferences[$field] = $usePreferences
                ? array_values(array_diff($ids, $filters['skip_'.$field] ?? [], $manual))
                : [];
            $effectiveFilters[$field] = array_merge($manual, $appliedPreferences[$field]);
        }
        $excludedIngredients = $effectiveFilters['exclude_ingredients'];
        $dietaryTags = $effectiveFilters['dietary_tags'];

        if (isset($filters['difficulty'])) {
            $query->whereIn('difficulty', $filters['difficulty']);
        }
        if (isset($filters['max_time'])) {
            $query->whereRaw('preparation_minutes + cooking_minutes <= ?', [(int) $filters['max_time']]);
        }

        if (isset($filters['q'])) {
            $search = strtr($filters['q'], ['!' => '!!', '%' => '!%', '_' => '!_']);
            $query->whereRaw("LOWER(title) LIKE LOWER(?) ESCAPE '!'", ["%{$search}%"]);
        }

        if (isset($filters['categories'])) {
            $included = $this->expandCategories($categoryTree, $filters['categories']);
            $query->whereHas('categories', fn (Builder $categories): Builder => $categories->whereKey($included));
        }

        if ($excluded !== []) {
            $query->whereDoesntHave('categories', fn (Builder $categories): Builder => $categories->whereKey($excluded));
        }

        if ($excludedIngredients !== []) {
            $query->whereDoesntHave('ingredients', fn (Builder $ingredients): Builder => $ingredients->whereKey(array_map('intval', $excludedIngredients)));
        }

        $includedIngredients = array_map('intval', $filters['ingredients'] ?? []);
        if ($includedIngredients !== []) {
            $matchCount = isset($filters['require_all_ingredients']) ? count($includedIngredients) : 1;
            $query->whereHas('ingredients', fn (Builder $ingredients): Builder => $ingredients->whereKey($includedIngredients), '>=', $matchCount);
        }

        foreach ($dietaryTags as $id) {
            $query->whereHas('dietaryTags', fn (Builder $tags): Builder => $tags->whereKey((int) $id));
        }

        $selectedCategoryBranches = [];
        foreach (array_unique(array_merge($filters['categories'] ?? [], $filters['exclude_categories'] ?? [])) as $id) {
            $selectedCategoryBranches[$id] = $this->expandCategories($categoryTree, [$id]);
        }

        return view('recipes.index', [
            'recipes' => $query
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->paginate(12)
                ->appends($filters),
            'filters' => $filters,
            'effectiveFilters' => $effectiveFilters,
            'savedPreferences' => $savedPreferences,
            'appliedPreferences' => $appliedPreferences,
            'savedAllergens' => $user?->allergens()->orderBy('name')->pluck('name') ?? collect(),
            'difficulties' => RecipeDifficulty::cases(),
            'categoryOptions' => $this->categoryOptions($categoryTree),
            'selectedCategoryBranches' => $selectedCategoryBranches,
            'categoryParents' => $categoryTree->collapse()->pluck('parent_id', 'id'),
            'ingredientOptions' => Ingredient::query()->orderBy('name')->pluck('name', 'id'),
            'dietaryTagOptions' => DietaryTag::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    private function categoryOptions(Collection $tree, ?int $parentId = null, string $prefix = ''): array
    {
        $options = [];

        foreach ($tree->get($parentId ?? '', []) as $category) {
            $label = $prefix.$category->name;
            $options[$category->id] = $label;
            $options += $this->categoryOptions($tree, $category->id, $label.' › ');
        }

        return $options;
    }

    private function expandCategories(Collection $tree, array $selected): array
    {
        $expanded = [];
        $pending = array_values($selected);
        while ($pending !== []) {
            $id = (int) array_pop($pending);
            if (isset($expanded[$id])) {
                continue;
            }

            $expanded[$id] = true;
            foreach ($tree->get($id, []) as $child) {
                $pending[] = $child->id;
            }
        }

        return array_keys($expanded);
    }

    public function show(Request $request, string $recipe): View
    {
        $publishedRecipe = Recipe::query()
            ->published()
            ->where('slug', $recipe)
            ->with(['author', 'categories', 'dietaryTags', 'ingredients', 'steps', 'thumbnail'])
            ->withAllergenWarnings($request->user())
            ->firstOrFail();

        return view('recipes.show', [
            'recipe' => $publishedRecipe,
            'comments' => $publishedRecipe->comments()->with('author')->orderByDesc('id')
                ->paginate(Comment::RECIPE_PAGE_SIZE, pageName: 'comments_page')->fragment('comments'),
            'isSaved' => $request->user()?->savedRecipes()->wherePivot('recipe_id', $publishedRecipe->id)->exists() ?? false,
        ]);
    }
}
