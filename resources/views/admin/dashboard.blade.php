@extends('layouts.app')

@section('title', 'Administration | WhiskList')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-orange-700">Administration</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">WhiskList overview</h1>
            <p class="mt-2 text-stone-600">Manage the application data and moderate community content.</p>
        </div>

        <div class="mt-10 grid gap-6 md:grid-cols-3">
            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                <p class="text-sm font-semibold text-stone-600">Registered users</p>
                <p class="mt-3 text-4xl font-semibold">{{ $totalUsers }}</p>
            </section>

            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                <p class="text-sm font-semibold text-stone-600">Administrators</p>
                <p class="mt-3 text-4xl font-semibold">{{ $administratorCount }}</p>
            </section>

            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                <p class="text-sm font-semibold text-stone-600">Public profiles</p>
                <p class="mt-3 text-4xl font-semibold">{{ $publicProfileCount }}</p>
            </section>
        </div>

        <div class="mt-8 grid gap-6 md:grid-cols-2">
            <section class="rounded-2xl border border-dashed border-stone-300 p-8">
                <h2 class="text-lg font-semibold">Categories and ingredients</h2>
                <p class="mt-2 text-sm leading-6 text-stone-600">Management tools will appear here after the cooking-domain models are implemented.</p>
            </section>

            <section class="rounded-2xl border border-dashed border-stone-300 p-8">
                <h2 class="text-lg font-semibold">Content moderation</h2>
                <p class="mt-2 text-sm leading-6 text-stone-600">Recipe and comment moderation will be added with their respective features.</p>
            </section>
        </div>
    </section>
@endsection
