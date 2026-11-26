<section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-baseline justify-between gap-3">
        <h2 class="text-2xl font-semibold">Latest recipes</h2>
        <a class="font-semibold text-orange-700 hover:underline" href="{{ route('recipes.index', absolute: ! ($relativeLinks ?? false)) }}">Browse all recipes</a>
    </div>
    @if ($latestRecipes->isEmpty())
        <p class="rounded-2xl bg-white p-6 text-stone-600 ring-1 ring-stone-200">No recipes have been published yet.</p>
    @else
        <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($latestRecipes as $recipe)
                <x-recipe-card :recipe="$recipe" :relative-links="$relativeLinks ?? false" />
            @endforeach
        </ul>
    @endif
</section>
