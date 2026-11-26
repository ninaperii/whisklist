@extends('layouts.app')

@section('title', 'Recipes | WhiskList')

@section('content')
    @php
        $groups = [
            'categories' => ['Include categories', $categoryOptions],
            'exclude_categories' => ['Exclude categories', $categoryOptions],
            'ingredients' => ['Include ingredients', $ingredientOptions],
            'exclude_ingredients' => ['Exclude ingredients', $ingredientOptions],
            'dietary_tags' => ['Dietary tags', $dietaryTagOptions],
        ];
        $quickFilters = [
            'q' => isset($filters['q']) ? 'Search: '.$filters['q'] : null,
            'max_time' => isset($filters['max_time']) ? $filters['max_time'].' min or less' : null,
            'require_all_ingredients' => isset($filters['require_all_ingredients']) ? 'Match all ingredients' : null,
        ];
        $chipClass = 'rounded-md bg-orange-50 px-3 py-1.5 text-sm font-semibold text-orange-800 hover:bg-orange-100';
        $pillClass = 'inline-flex cursor-pointer rounded-full border border-stone-300 px-3 py-1.5 text-sm hover:border-orange-700 peer-checked:border-orange-700 peer-checked:bg-orange-700 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-orange-700';
    @endphp
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="mb-6 text-3xl font-semibold tracking-tight">Recipes</h1>
        @if (session('status'))
            <p class="mb-5 rounded-lg bg-green-50 p-4 text-green-800" role="status">{{ session('status') }}</p>
        @endif
        @if (session('recipe_preference_status'))
            <p class="mb-5 rounded-lg bg-green-50 p-4 text-green-800" role="status">{{ session('recipe_preference_status') }}</p>
        @endif
        <button class="mb-4 hidden rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm font-semibold hover:bg-stone-50 lg:inline-flex"
            type="button" aria-expanded="true" aria-controls="recipe-filter-sidebar" data-toggle-filter-sidebar hidden>
            ← Hide filters
        </button>
        <div class="group grid items-start gap-8 lg:grid-cols-[19rem_minmax(0,1fr)] lg:data-[filters-collapsed]:grid-cols-1" data-filter-layout>
            <form id="recipe-filter-sidebar" class="rounded-2xl bg-white p-5 ring-1 ring-stone-200 lg:group-data-[filters-collapsed]:hidden" method="GET" action="{{ route('recipes.index') }}" data-recipe-filters>
                @if ($errors->any())
                    <p class="mb-4 text-sm text-red-700" role="alert">{{ $errors->first('category_conflict') ?: ($errors->first('ingredient_conflict') ?: 'Some filters were invalid or are no longer available. Choose your filters again.') }}</p>
                @endif
                <label class="block font-semibold" for="recipe-search">Search by recipe title</label>
                <input class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2" id="recipe-search" type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="120">

                <details class="mt-5" open data-filter-panel>
                    <summary class="cursor-pointer font-semibold">Filters</summary>
                    <div class="mt-4 space-y-4">
                        <fieldset>
                            <legend class="mb-2 text-sm font-semibold">Difficulty</legend>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($difficulties as $difficulty)
                                    <label>
                                        <input class="peer sr-only" type="checkbox" name="difficulty[]" value="{{ $difficulty->value }}" @checked(in_array($difficulty->value, $filters['difficulty'] ?? []))>
                                        <span class="{{ $pillClass }}">{{ $difficulty->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend class="mb-2 text-sm font-semibold">Total time <span class="font-normal text-stone-600">(prep + cook)</span></legend>
                            <div class="flex flex-wrap gap-2">
                                @foreach (['' => 'Any time', 20 => '≤ 20 min', 30 => '≤ 30 min', 45 => '≤ 45 min'] as $minutes => $label)
                                    <label>
                                        <input class="peer sr-only" type="radio" name="max_time" value="{{ $minutes }}" @checked((string) ($filters['max_time'] ?? '') === (string) $minutes)>
                                        <span class="{{ $pillClass }}">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        @foreach ([
                            'Categories' => [['categories', 'exclude_categories'], 'Including a category also includes its subcategories.'],
                            'Dietary tags' => [['dietary_tags'], 'Matches every selected tag.'],
                            'Ingredients' => [['ingredients', 'exclude_ingredients'], 'Excluded ingredients are always filtered out.'],
                        ] as $group => [$fields, $hint])
                            @if (count($groups[$fields[0]][1]))
                                <details class="border-t border-stone-200 pt-3" @if (collect($fields)->contains(fn ($field) => !empty($effectiveFilters[$field]))) open @endif>
                                    <summary class="cursor-pointer font-semibold">{{ $group }}</summary>
                                    <p class="my-2 text-sm text-stone-600">{{ $hint }}</p>
                                    @foreach ($fields as $field)
                                        @php
                                            [$label, $options] = $groups[$field];
                                        @endphp
                                        @if ($field === 'dietary_tags')
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($options as $id => $name)
                                                    <label>
                                                        <input class="peer sr-only" type="checkbox" name="dietary_tags[]" value="{{ $id }}"
                                                            @if (in_array($id, $savedPreferences[$field])) data-saved-filter="{{ $field }}" @endif
                                                            @if (in_array($id, $appliedPreferences[$field])) data-automatic-filter @endif
                                                            @checked(in_array($id, $effectiveFilters[$field]))>
                                                        <span class="{{ $pillClass }}">{{ $name }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @else
                                            <fieldset class="mt-3">
                                                <legend class="mb-1 text-sm font-semibold">{{ $label }}</legend>
                                                @if ($field === 'ingredients')
                                                    <label class="mb-2 flex items-center gap-2 text-sm">
                                                        <input type="checkbox" name="require_all_ingredients" value="1" @checked($filters['require_all_ingredients'] ?? false)>
                                                        Include all selected ingredients
                                                    </label>
                                                @endif
                                                <x-search-picker :field="$field" :label="$label" :options="$options" :selected="$effectiveFilters[$field] ?? []"
                                                    :parents="in_array($field, ['categories', 'exclude_categories'], true) ? $categoryParents : null"
                                                    :saved="$savedPreferences[$field] ?? []" :applied="$appliedPreferences[$field] ?? []" :compact-search="true" />
                                            </fieldset>
                                        @endif
                                    @endforeach
                                </details>
                            @endif
                        @endforeach

                        @auth
                            <section class="space-y-2 border-t border-stone-200 pt-3">
                                <label class="flex items-center gap-2 text-sm font-semibold">
                                    <input type="checkbox" name="use_preferences" value="1" @checked($filters['use_preferences'] ?? false)>
                                    Use my preferences
                                </label>
                                <a class="text-sm text-orange-700 hover:underline" href="{{ route('profile.edit', ['return_search' => '?'.http_build_query($filters + ($recipes->currentPage() > 1 ? ['page' => $recipes->currentPage()] : []))]) }}#recipe-preferences" data-edit-recipe-preferences>Edit preferences</a>
                                @if ($savedAllergens->isNotEmpty())
                                    <p class="text-sm text-stone-600">Allergen warnings for {{ $savedAllergens->join(', ') }} are active.</p>
                                @endif
                            </section>
                        @endauth
                    </div>
                </details>

                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <button class="rounded-lg bg-orange-700 px-4 py-2.5 font-semibold text-white" type="submit">Apply filters</button>
                    <a class="font-semibold text-orange-700 hover:underline" href="{{ route('recipes.index') }}">Clear filters</a>
                </div>
            </form>

            <div>
                <p class="sr-only" role="status" data-recipe-search-status></p>
                <div class="mb-4 rounded-lg bg-amber-50 p-4 text-amber-950" hidden data-recipe-search-error>
                    <p role="alert">Couldn’t update recipes. The previous results are still shown.</p>
                    <button class="mt-2 font-semibold underline" type="button" data-retry-recipe-search>Retry</button>
                </div>
                <div data-recipe-results>
                <p class="mb-4 font-semibold text-stone-700" data-recipe-result-count>{{ $recipes->total() }} {{ Str::plural('recipe', $recipes->total()) }}</p>
                <div class="mb-5 flex flex-wrap gap-2" aria-label="Applied filters">
                    @foreach ($quickFilters as $field => $label)
                        @if ($label)
                            <a class="{{ $chipClass }}" href="{{ route('recipes.index', Arr::except($filters, [$field])) }}" aria-label="Remove {{ $label }}">{{ $label }} ×</a>
                        @endif
                    @endforeach
                    @foreach (['difficulty' => ['Difficulty', collect($difficulties)->mapWithKeys(fn ($level) => [$level->value => $level->name])]] + $groups as $field => [$label, $options])
                        @foreach ($effectiveFilters[$field] ?? [] as $id)
                            @php
                                $remaining = $filters;
                                $fromPreferences = in_array($id, $appliedPreferences[$field] ?? []);
                                $name = $options[$id];
                                if ($fromPreferences) {
                                    $remaining['skip_'.$field][] = $id;
                                } else {
                                    $removed = in_array($field, ['categories', 'exclude_categories'], true)
                                        ? $selectedCategoryBranches[$id]
                                        : [$id];
                                    $remaining[$field] = array_values(array_diff($remaining[$field] ?? [], $removed));
                                    if (!empty($filters['use_preferences']) && in_array($id, $savedPreferences[$field] ?? [])) {
                                        $remaining['skip_'.$field][] = $id;
                                    }
                                }
                            @endphp
                            <a class="{{ $chipClass }}" href="{{ route('recipes.index', $remaining) }}" aria-label="Remove {{ $label }}: {{ $name }}">
                                {{ str_starts_with($field, 'exclude_') ? 'Exclude: ' : '' }}{{ $name }}{{ $fromPreferences ? ' (from preferences)' : '' }} ×
                            </a>
                        @endforeach
                    @endforeach
                </div>

                @if ($recipes->isEmpty())
                    <p class="rounded-2xl bg-white p-6 text-stone-600 ring-1 ring-stone-200">
                        @if ($recipes->currentPage() > 1)
                            No recipes on this page.
                        @elseif ($filters !== [])
                            No recipes match these filters.
                        @else
                            No recipes have been published yet.
                        @endif
                    </p>
                    @if ($recipes->currentPage() > 1)
                        <a class="mt-4 inline-block font-semibold text-orange-700 hover:underline" href="{{ route('recipes.index', $filters) }}">Back to the first page</a>
                    @endif
                @else
                    <ul class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($recipes as $recipe)
                            <x-recipe-card :recipe="$recipe" heading="h2">
                                @auth
                                    <form class="flex flex-wrap items-center justify-end gap-2 px-4 pb-3" method="POST" action="{{ route($recipe->is_saved ? 'whisklist.destroy' : 'whisklist.store', $recipe) }}" data-recipe-heart
                                        data-save-label="Save {{ $recipe->title }} to My WhiskList" data-remove-label="Remove {{ $recipe->title }} from My WhiskList">
                                        @csrf
                                        <input type="hidden" name="_method" value="DELETE" @disabled(! $recipe->is_saved)>
                                        <input type="hidden" name="from" value="recipes">
                                        <input type="hidden" name="return_search" value="{{ http_build_query($filters + ($recipes->currentPage() > 1 ? ['page' => $recipes->currentPage()] : [])) }}">
                                        @php($saveLabel = $recipe->is_saved ? 'Remove '.$recipe->title.' from My WhiskList' : 'Save '.$recipe->title.' to My WhiskList')
                                        <button class="inline-flex size-10 items-center justify-center rounded-full border border-stone-300 bg-white text-orange-700 hover:bg-orange-50" type="submit" aria-label="{{ $saveLabel }}" title="{{ $saveLabel }}">
                                            <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="{{ $recipe->is_saved ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0l-1 1-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/>
                                            </svg>
                                        </button>
                                        <p class="w-full text-right text-sm text-stone-600" role="status" hidden data-heart-status></p>
                                    </form>
                                @endauth
                            </x-recipe-card>
                        @endforeach
                    </ul>
                    <div class="mt-8">{{ $recipes->links() }}</div>
                @endif
                </div>
            </div>
        </div>
    </section>
@endsection
