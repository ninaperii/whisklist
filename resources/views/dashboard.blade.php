@extends('layouts.app')

@section('title', 'Dashboard | WhiskList')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-orange-700">Your kitchen</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">Welcome, {{ $user->name }}</h1>
                <p class="mt-2 text-stone-600">Manage your recipes and keep track of dishes you want to try.</p>
            </div>

            <div class="rounded-lg bg-white px-4 py-3 text-sm ring-1 ring-stone-200">
                <p>Profile visibility: <span class="font-semibold">{{ $profileVisibility }}</span></p>
                <div class="mt-2 flex gap-3">
                    <a class="font-semibold text-orange-700 hover:text-orange-800" href="{{ route('users.show', $user) }}">View</a>
                    <a class="font-semibold text-orange-700 hover:text-orange-800" href="{{ route('profile.edit') }}">Edit</a>
                </div>
            </div>
        </div>

        <div class="mt-10 grid gap-6 md:grid-cols-3">
            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                <p class="text-sm font-semibold text-orange-700">Published</p>
                <h2 class="mt-2 text-xl font-semibold">My recipes</h2>
                <p class="mt-6 text-3xl font-semibold">{{ $publishedCount }}</p>
                <a class="mt-3 inline-block font-semibold text-orange-700" href="{{ route('recipes.mine') }}">Manage my recipes</a>
            </section>

            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                <p class="text-sm font-semibold text-orange-700">In progress</p>
                <h2 class="mt-2 text-xl font-semibold">Drafts</h2>
                <p class="mt-6 text-3xl font-semibold">{{ $draftCount }}</p>
                <a class="mt-3 inline-block font-semibold text-orange-700" href="{{ route('recipes.mine', ['status' => 'draft']) }}">View drafts</a>
            </section>

            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                <p class="text-sm font-semibold text-orange-700">Saved favorites</p>
                <h2 class="mt-2 text-xl font-semibold">My WhiskList</h2>
                <p class="mt-6 text-3xl font-semibold">{{ $savedCount }}</p>
                <a class="mt-3 inline-block font-semibold text-orange-700 hover:underline" href="{{ route('whisklist.index') }}">View saved recipes</a>
            </section>
        </div>

        <a class="mt-8 inline-block rounded-lg bg-orange-700 px-5 py-2.5 font-semibold text-white" href="{{ route('recipes.create') }}">Create recipe</a>
    </section>
@endsection
