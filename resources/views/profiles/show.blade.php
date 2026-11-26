@extends('layouts.app')

@section('title', $profile->name.' | WhiskList')

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200 sm:p-8">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-center gap-4">
                    @if ($profile->avatar_path)
                        <img class="size-16 shrink-0 rounded-full object-cover" src="{{ route('users.avatar', $profile) }}" alt="">
                    @else
                        <div class="flex size-16 shrink-0 items-center justify-center rounded-full bg-orange-100 text-2xl font-semibold text-orange-800" aria-hidden="true">
                            {{ str($profile->name)->substr(0, 1)->upper() }}
                        </div>
                    @endif
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-orange-700">Cook profile</p>
                        <h1 class="mt-1 text-3xl font-semibold tracking-tight">{{ $profile->name }}</h1>
                    </div>
                </div>

                @if (auth()->id() === $profile->id)
                    <a class="rounded-lg border border-stone-300 px-4 py-2 text-center text-sm font-semibold hover:bg-stone-50" href="{{ route('profile.edit') }}">
                        Edit profile
                    </a>
                @endif
            </div>

            @if ($showDetails)
                <div class="mt-8 border-t border-stone-200 pt-8">
                    <h2 class="text-lg font-semibold">About</h2>
                    <p class="mt-3 whitespace-pre-line leading-7 text-stone-600">
                        {{ $profile->bio ?: 'This cook has not added a biography yet.' }}
                    </p>
                </div>
            @endif

            @unless ($profile->is_profile_public)
                <p class="mt-6 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200">
                    This profile is private.
                </p>
            @endunless
        </div>

        <section class="mt-8">
            <h2 class="mb-4 text-xl font-semibold">Published recipes</h2>
            @if ($recipes->isEmpty())
                <p class="text-sm text-stone-600">No published recipes on this page.</p>
            @else
                <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($recipes as $recipe)
                        <x-recipe-card :recipe="$recipe" origin="profile" :origin-query="request()->only('page')" />
                    @endforeach
                </ul>
            @endif
            <div class="mt-6">{{ $recipes->links() }}</div>
        </section>
    </section>
@endsection
