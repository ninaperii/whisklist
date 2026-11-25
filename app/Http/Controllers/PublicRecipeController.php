<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DietaryTag;
use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PublicRecipeController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $validator = Validator::make($request->query(), [
            'q' => ['nullable', 'string', 'max:120'],
            'categories' => ['sometimes', 'array', 'max:100'],
            'categories.*' => ['required', 'integer', 'distinct', 'exists:categories,id'],
            'exclude_categories' => ['sometimes', 'array', 'max:100'],
            'exclude_categories.*' => ['required', 'integer', 'distinct', 'exists:categories,id'],
            'ingredients' => ['sometimes', 'array', 'max:50'],
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
        if (array_intersect($filters['categories'] ?? [], $filters['exclude_categories'] ?? []) !== []) {
            return to_route('recipes.index')->withErrors([
                'category_conflict' => 'Choose either Include or Exclude for each category.',
            ]);
        }

        if (array_intersect($filters['ingredients'] ?? [], $filters['exclude_ingredients'] ?? []) !== []) {
            return to_route('recipes.index')->withErrors([
                'ingredient_conflict' => 'Choose either Include or Exclude for each ingredient.',
            ]);
        }

        $categoryTree = Category::query()->orderBy('name')->get()->groupBy('parent_id');
        $query = Recipe::query()->published()->with('thumbnail');

        if (isset($filters['q'])) {
            $search = strtr($filters['q'], ['!' => '!!', '%' => '!%', '_' => '!_']);
            $query->whereRaw("LOWER(title) LIKE LOWER(?) ESCAPE '!'", ["%{$search}%"]);
        }

        if (isset($filters['categories'])) {
            $included = $this->expandCategories($categoryTree, $filters['categories']);
            $query->whereHas('categories', fn (Builder $categories): Builder => $categories->whereKey($included));
        }

        if (isset($filters['exclude_categories'])) {
            $excluded = $this->expandCategories($categoryTree, $filters['exclude_categories']);
            $query->whereDoesntHave('categories', fn (Builder $categories): Builder => $categories->whereKey($excluded));
        }

        if (isset($filters['exclude_ingredients'])) {
            $excludedIngredients = array_map('intval', $filters['exclude_ingredients']);
            $query->whereDoesntHave('ingredients', fn (Builder $ingredients): Builder => $ingredients->whereKey($excludedIngredients));
        }

        foreach (['ingredients' => 'ingredients', 'dietary_tags' => 'dietaryTags'] as $filter => $relationship) {
            foreach ($filters[$filter] ?? [] as $id) {
                $query->whereHas($relationship, fn (Builder $related): Builder => $related->whereKey((int) $id));
            }
        }

        return view('recipes.index', [
            'recipes' => $query
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->paginate(12)
                ->appends($filters),
            'filters' => $filters,
            'categoryOptions' => $this->categoryOptions($categoryTree),
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
