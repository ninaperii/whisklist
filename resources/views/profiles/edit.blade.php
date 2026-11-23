@extends('layouts.app')

@section('title', 'Edit profile | WhiskList')

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200 sm:p-8">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-orange-700">Account settings</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">Edit your profile</h1>
                <p class="mt-2 text-sm text-stone-600">Choose what other WhiskList visitors can see about you.</p>
            </div>

            @if (session('status'))
                <p class="mt-6 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800 ring-1 ring-green-200">
                    {{ session('status') }}
                </p>
            @endif

            <form class="mt-8 space-y-6" method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium" for="name">Name</label>
                    <input
                        class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 outline-none focus:border-orange-600 focus:ring-2 focus:ring-orange-200"
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name', $user->name) }}"
                        maxlength="255"
                        required
                    >
                    @error('name')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium" for="bio">Biography</label>
                    <textarea
                        class="mt-2 min-h-36 w-full rounded-lg border border-stone-300 px-3 py-2 outline-none focus:border-orange-600 focus:ring-2 focus:ring-orange-200"
                        id="bio"
                        name="bio"
                        maxlength="1000"
                    >{{ old('bio', $user->bio) }}</textarea>
                    <p class="mt-2 text-sm text-stone-500">Up to 1,000 characters.</p>
                    @error('bio')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-xl bg-stone-50 p-4 ring-1 ring-stone-200">
                    <input name="is_profile_public" type="hidden" value="0">
                    <label class="flex items-start gap-3" for="is_profile_public">
                        <input
                            class="mt-1 rounded border-stone-300 text-orange-700 focus:ring-orange-600"
                            id="is_profile_public"
                            name="is_profile_public"
                            type="checkbox"
                            value="1"
                            @checked(old('is_profile_public', $user->is_profile_public))
                        >
                        <span>
                            <span class="block font-semibold">Public profile</span>
                            <span class="mt-1 block text-sm leading-6 text-stone-600">
                                When disabled, only you and administrators can open your profile page. Your published recipes remain publicly discoverable.
                            </span>
                        </span>
                    </label>
                    @error('is_profile_public')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap gap-3">
                    <button class="rounded-lg bg-orange-700 px-5 py-2.5 font-semibold text-white hover:bg-orange-800" type="submit">
                        Save profile
                    </button>
                    <a class="rounded-lg border border-stone-300 px-5 py-2.5 font-semibold hover:bg-stone-50" href="{{ route('users.show', $user) }}">
                        View profile
                    </a>
                </div>
            </form>
        </div>
    </section>
@endsection
