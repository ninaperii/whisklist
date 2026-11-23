@extends('layouts.app')

@section('title', 'Register | WhiskList')

@section('content')
    <h1 class="text-2xl font-semibold">Create your account</h1>
    <p class="mt-2 text-sm text-stone-600">Join WhiskList to publish recipes, comment, and save favorites.</p>

    <form class="mt-8 space-y-6" method="POST" action="{{ route('register.store') }}">
        @csrf

        <div>
            <label class="block text-sm font-medium" for="name">Name</label>
            <input
                class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 outline-none focus:border-orange-600 focus:ring-2 focus:ring-orange-200"
                id="name"
                name="name"
                type="text"
                value="{{ old('name') }}"
                autocomplete="name"
                required
                autofocus
            >
            @error('name')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium" for="email">Email address</label>
            <input
                class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 outline-none focus:border-orange-600 focus:ring-2 focus:ring-orange-200"
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                autocomplete="email"
                required
            >
            @error('email')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium" for="password">Password</label>
            <input
                class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 outline-none focus:border-orange-600 focus:ring-2 focus:ring-orange-200"
                id="password"
                name="password"
                type="password"
                autocomplete="new-password"
                required
            >
            @error('password')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium" for="password_confirmation">Confirm password</label>
            <input
                class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 outline-none focus:border-orange-600 focus:ring-2 focus:ring-orange-200"
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                autocomplete="new-password"
                required
            >
        </div>

        <button class="w-full rounded-lg bg-orange-700 px-4 py-2.5 font-semibold text-white hover:bg-orange-800" type="submit">
            Register
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-stone-600">
        Already have an account?
        <a class="font-semibold text-orange-700 hover:text-orange-800" href="{{ route('login') }}">Log in</a>
    </p>
@endsection
