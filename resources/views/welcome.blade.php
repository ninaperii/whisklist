@extends('layouts.app')

@section('title', 'WhiskList | Cook, share, discover')

@section('content')
    <section class="mx-auto grid max-w-7xl gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:items-center lg:px-8 lg:py-28">
        <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-orange-700">Cook, share, discover</p>
            <h1 class="mt-4 text-4xl font-semibold tracking-tight sm:text-6xl">Find your next favorite recipe.</h1>
            <p class="mt-6 max-w-xl text-lg leading-8 text-stone-600">
                WhiskList is a community for discovering recipes, sharing what you cook, and saving dishes you want to try.
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                @guest
                    <a class="rounded-lg bg-orange-700 px-5 py-3 font-semibold text-white hover:bg-orange-800" href="{{ route('register') }}">
                        Create an account
                    </a>
                    <a class="rounded-lg border border-stone-300 bg-white px-5 py-3 font-semibold hover:bg-stone-50" href="{{ route('login') }}">
                        Log in
                    </a>
                @else
                    <a class="rounded-lg bg-orange-700 px-5 py-3 font-semibold text-white hover:bg-orange-800" href="{{ route('dashboard') }}">
                        Open your dashboard
                    </a>
                @endguest
            </div>
        </div>

    </section>

    <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        <h2 class="mb-6 text-2xl font-semibold">Latest recipes</h2>
        @if ($latestRecipes->isEmpty())
            <p class="rounded-2xl bg-white p-6 text-stone-600 ring-1 ring-stone-200">No recipes have been published yet.</p>
        @else
            <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($latestRecipes as $recipe)
                    <li class="overflow-hidden rounded-2xl bg-white ring-1 ring-stone-200">
                        <a class="group block" href="{{ route('recipes.show', $recipe) }}">
                            @if ($recipe->thumbnail)
                                <img class="aspect-video w-full object-cover" src="{{ route('recipes.thumbnail', $recipe) }}" alt="{{ $recipe->thumbnail->alt_text ?? '' }}" loading="lazy">
                            @endif
                            <div class="p-5">
                                <h3 class="text-lg font-semibold group-hover:underline">{{ $recipe->title }}</h3>
                                @if ($recipe->summary !== '')
                                    <p class="mt-2 line-clamp-2 text-sm text-stone-600">{{ $recipe->summary }}</p>
                                @endif
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
