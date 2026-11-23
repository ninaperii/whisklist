@extends('layouts.app')

@section('title', 'Log in | WhiskList')

@section('content')
    <h1 class="text-2xl font-semibold">Welcome back</h1>
    <p class="mt-2 text-sm text-stone-600">Log in to share recipes, comment, and open your WhiskList.</p>

    <form class="mt-8 space-y-6" method="POST" action="{{ route('login.store') }}">
        @csrf

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
                autofocus
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
                autocomplete="current-password"
                required
            >
            @error('password')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-stone-700">
            <input class="rounded border-stone-300 text-orange-700 focus:ring-orange-600" name="remember" type="checkbox" value="1">
            Remember me
        </label>

        <button class="w-full rounded-lg bg-orange-700 px-4 py-2.5 font-semibold text-white hover:bg-orange-800" type="submit">
            Log in
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-stone-600">
        New to WhiskList?
        <a class="font-semibold text-orange-700 hover:text-orange-800" href="{{ route('register') }}">Create an account</a>
    </p>
@endsection
