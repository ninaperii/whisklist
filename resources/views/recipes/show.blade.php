@extends('layouts.app')

@section('title', $recipe->title . ' | WhiskList')

@section('content')
    <article class="mx-auto max-w-5xl px-4 py-12 sm:px-6">
        <div class="mb-6">
            @can('update', $recipe)
                <a class="font-semibold text-orange-700 hover:underline" href="{{ route('recipes.mine') }}">← My recipes</a>
            @else
                <a class="font-semibold text-orange-700 hover:underline" href="{{ route('recipes.index') }}">← All recipes</a>
            @endcan
        </div>

        @if (session('status'))
            <p role="status" class="mb-6 rounded-lg bg-green-50 p-4 text-green-800"
                @if (session('published_from_editor') && auth()->id() === $recipe->user_id)
                    data-published-from="{{ session('published_from_editor') }}" data-published-editor="{{ route('recipes.edit', $recipe, false) }}"
                @endif>{{ session('status') }}</p>
        @endif
        <header>
            <h1 class="text-4xl font-semibold tracking-tight">{{ $recipe->title }}</h1>
            @if ($recipe->author->is_profile_public)
                <p class="mt-2 text-stone-600">By <a class="font-semibold text-orange-700 hover:underline" href="{{ route('users.show', $recipe->author) }}">{{ $recipe->author->name }}</a></p>
            @endif
            @if ($recipe->summary !== '')
                <p class="mt-4 whitespace-pre-line text-lg text-stone-700">{{ $recipe->summary }}</p>
            @endif
        </header>

        @php($allergenMatches = $recipe->matchedAllergenNames())
        @if ($allergenMatches->isNotEmpty())
            <aside class="mt-6 rounded-lg bg-amber-50 p-4 text-amber-950">
                <p class="font-semibold">Allergen match: {{ $allergenMatches->join(', ') }}</p>
                <p class="mt-1 text-sm">This warning uses the recorded ingredients and known allergen links.</p>
            </aside>
        @endif

        @if ($recipe->thumbnail)
            <img class="mt-6 max-h-96 w-full rounded-2xl object-cover" src="{{ route('recipes.thumbnail', $recipe) }}" alt="{{ $recipe->thumbnail->alt_text ?? $recipe->title }}">
        @endif

        <dl class="my-8 grid grid-cols-2 gap-4 rounded-2xl bg-white p-5 ring-1 ring-stone-200 sm:grid-cols-4">
            @foreach (['Prep time' => $recipe->preparation_minutes . ' min', 'Cook time' => $recipe->cooking_minutes . ' min', 'Servings' => $recipe->servings, 'Difficulty' => $recipe->difficulty->name] as $label => $value)
                <div>
                    <dt class="text-sm text-stone-600">{{ $label }}</dt>
                    <dd class="mt-1 font-semibold">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        @foreach (['Categories' => $recipe->categories, 'Dietary tags' => $recipe->dietaryTags] as $label => $options)
            @if ($options->isNotEmpty())
                <div class="mb-4 flex flex-wrap items-baseline gap-2">
                    <h2 class="text-sm font-semibold text-stone-700">{{ $label }}</h2>
                    <ul class="flex flex-wrap gap-2">
                        @foreach ($options as $option)
                            <li class="rounded-full bg-orange-50 px-3 py-1 text-sm text-orange-900">{{ $option->name }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach

        <div class="mt-8 grid items-start gap-6 md:grid-cols-3">
            <section class="rounded-2xl bg-white p-5 ring-1 ring-stone-200">
                <h2 class="text-xl font-semibold">Ingredients</h2>
                <ul class="mt-4 divide-y divide-stone-200">
                    @foreach ($recipe->ingredients as $ingredient)
                        <li class="py-2">
                            @if ($ingredient->pivot->quantity !== null)
                                {{ rtrim(rtrim(number_format((float) $ingredient->pivot->quantity, 2, '.', ''), '0'), '.') }}
                            @endif
                            {{ $ingredient->pivot->unit }}
                            {{ $ingredient->name }}
                            @if ($ingredient->pivot->notes)
                                <span class="text-sm text-stone-600">({{ $ingredient->pivot->notes }})</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="rounded-2xl bg-white p-5 ring-1 ring-stone-200 md:col-span-2">
                <h2 class="text-xl font-semibold">Instructions</h2>
                <ol class="mt-4 list-decimal space-y-4 pl-6 marker:font-semibold marker:text-orange-700">
                    @foreach ($recipe->steps as $step)
                        <li class="whitespace-pre-line pl-1">{{ $step->instruction }}</li>
                    @endforeach
                </ol>
            </section>
        </div>
    </article>
@endsection
