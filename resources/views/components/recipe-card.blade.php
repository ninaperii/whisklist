@props(['recipe', 'heading' => 'h3'])

<li class="overflow-hidden rounded-2xl bg-white ring-1 ring-stone-200">
    <a class="group block" href="{{ route('recipes.show', $recipe) }}">
        @if ($recipe->thumbnail)
            <img class="aspect-video w-full object-cover" src="{{ route('recipes.thumbnail', $recipe) }}" alt="{{ $recipe->thumbnail->alt_text ?? '' }}" loading="lazy">
        @endif
        <div class="p-5">
            <{{ $heading }} class="text-lg font-semibold group-hover:underline">{{ $recipe->title }}</{{ $heading }}>
            @if ($recipe->summary !== '')
                <p class="mt-2 line-clamp-2 text-sm text-stone-600">{{ $recipe->summary }}</p>
            @endif
        </div>
    </a>
</li>
