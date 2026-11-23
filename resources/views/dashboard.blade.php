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
                Profile visibility: <span class="font-semibold">{{ $profileVisibility }}</span>
            </div>
        </div>

        <div class="mt-10 grid gap-6 md:grid-cols-3">
            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                <p class="text-sm font-semibold text-orange-700">Published</p>
                <h2 class="mt-2 text-xl font-semibold">My recipes</h2>
                <p class="mt-3 text-sm leading-6 text-stone-600">Your published recipes will appear here once recipe creation is available.</p>
                <p class="mt-6 text-3xl font-semibold">0</p>
            </section>

            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                <p class="text-sm font-semibold text-orange-700">In progress</p>
                <h2 class="mt-2 text-xl font-semibold">Drafts</h2>
                <p class="mt-3 text-sm leading-6 text-stone-600">Recipes you are still preparing will be collected here.</p>
                <p class="mt-6 text-3xl font-semibold">0</p>
            </section>

            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                <p class="text-sm font-semibold text-orange-700">Saved favorites</p>
                <h2 class="mt-2 text-xl font-semibold">My WhiskList</h2>
                <p class="mt-3 text-sm leading-6 text-stone-600">Recipes you save for later will be available here.</p>
                <p class="mt-6 text-3xl font-semibold">0</p>
            </section>
        </div>

        <section class="mt-8 rounded-2xl border border-dashed border-stone-300 p-8 text-center">
            <h2 class="text-lg font-semibold">Recipe creation is coming next</h2>
            <p class="mt-2 text-sm text-stone-600">The dashboard is ready to display real recipe and favorite data as those features are implemented.</p>
        </section>
    </section>
@endsection
