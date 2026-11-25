@extends('layouts.app')

@section('title', 'Recipes | WhiskList')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="mb-6 text-3xl font-semibold tracking-tight">Recipes</h1>

        <form class="mb-8 space-y-5 rounded-2xl bg-white p-5 ring-1 ring-stone-200" method="GET" action="{{ route('recipes.index') }}" data-recipe-filters>
            @if ($errors->any())
                <p class="text-sm text-red-700" role="alert">{{ $errors->first('category_conflict') ?: ($errors->first('ingredient_conflict') ?: 'Some filters were invalid or are no longer available. Choose your filters again.') }}</p>
            @endif
            <label class="block font-semibold" for="recipe-search">Search by recipe title</label>
            <input class="w-full rounded-lg border border-stone-300 px-3 py-2" id="recipe-search" type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="120">

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    'categories' => ['Include categories', $categoryOptions, 'Includes subcategories. Matches any selected category.'],
                    'exclude_categories' => ['Exclude categories', $categoryOptions, 'Hides recipes in selected categories and their subcategories.'],
                    'ingredients' => ['Include ingredients', $ingredientOptions, 'Matches recipes containing every selected ingredient.'],
                    'exclude_ingredients' => ['Exclude ingredients', $ingredientOptions, 'Hides recipes containing any selected ingredient.'],
                    'dietary_tags' => ['Dietary tags', $dietaryTagOptions, 'Matches all selected tags.'],
                ] as $field => [$label, $options, $hint])
                    @if (count($options))
                        <details class="rounded-lg border border-stone-200 p-3" @if (!empty($filters[$field])) open @endif>
                            <summary class="cursor-pointer font-semibold">{{ $label }}</summary>
                            <p class="my-2 text-sm text-stone-600">{{ $hint }}</p>
                            <fieldset class="max-h-52 space-y-2 overflow-y-auto p-1">
                                <legend class="sr-only">{{ $label }}</legend>
                                @foreach ($options as $id => $name)
                                    <label class="flex items-start gap-2 text-sm">
                                        <input class="mt-1" type="checkbox" name="{{ $field }}[]" value="{{ $id }}"
                                            @if (in_array($field, ['categories', 'exclude_categories'], true))
                                                data-category-parent="{{ $categoryParents[$id] ?? '' }}"
                                            @endif
                                            @checked(in_array($id, $filters[$field] ?? []))>
                                        {{ $name }}
                                    </label>
                                @endforeach
                            </fieldset>
                        </details>
                    @endif
                @endforeach
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <button class="rounded-lg bg-orange-700 px-5 py-2.5 font-semibold text-white" type="submit">Search recipes</button>
                @if ($filters !== [])
                    <a class="font-semibold text-orange-700 hover:underline" href="{{ route('recipes.index') }}">Clear filters</a>
                @endif
            </div>
        </form>

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
            <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($recipes as $recipe)
                    <x-recipe-card :recipe="$recipe" heading="h2" />
                @endforeach
            </ul>
            <div class="mt-8">{{ $recipes->links() }}</div>
        @endif
    </section>
@endsection
