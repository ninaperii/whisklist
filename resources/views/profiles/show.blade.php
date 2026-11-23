@extends('layouts.app')

@section('title', $profile->name.' | WhiskList')

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200 sm:p-8">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex size-16 shrink-0 items-center justify-center rounded-full bg-orange-100 text-2xl font-semibold text-orange-800">
                        {{ str($profile->name)->substr(0, 1)->upper() }}
                    </div>
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

            <div class="mt-8 border-t border-stone-200 pt-8">
                <h2 class="text-lg font-semibold">About</h2>
                <p class="mt-3 whitespace-pre-line leading-7 text-stone-600">
                    {{ $profile->bio ?: 'This cook has not added a biography yet.' }}
                </p>
            </div>

            @unless ($profile->is_profile_public)
                <p class="mt-6 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200">
                    This profile is private. It is visible because you are the owner or an administrator.
                </p>
            @endunless
        </div>

        <section class="mt-8 rounded-2xl border border-dashed border-stone-300 p-8 text-center">
            <h2 class="text-lg font-semibold">Published recipes</h2>
            <p class="mt-2 text-sm text-stone-600">This user's published recipes will appear here after recipe creation is implemented.</p>
        </section>
    </section>
@endsection
