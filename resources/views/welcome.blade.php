<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>WhiskList</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-stone-100 text-stone-900 antialiased">
        <header class="border-b border-stone-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a class="text-2xl font-semibold tracking-tight text-orange-700" href="{{ route('home') }}">
                    WhiskList
                </a>

                <nav class="flex items-center gap-3" aria-label="Account navigation">
                    @guest
                        <a class="rounded-lg px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100" href="{{ route('login') }}">
                            Log in
                        </a>
                        <a class="rounded-lg bg-orange-700 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-800" href="{{ route('register') }}">
                            Register
                        </a>
                    @else
                        <span class="hidden text-sm text-stone-600 sm:inline">Hello, {{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-100" type="submit">
                                Log out
                            </button>
                        </form>
                    @endguest
                </nav>
            </div>
        </header>

        <main>
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
                            <p class="rounded-lg bg-white px-5 py-3 text-stone-700 ring-1 ring-stone-200">
                                You are logged in. Your dashboard is coming next.
                            </p>
                        @endguest
                    </div>
                </div>

                <div class="rounded-3xl bg-orange-100 p-8 ring-1 ring-orange-200 sm:p-12">
                    <div class="rounded-2xl bg-white p-8 shadow-sm">
                        <p class="text-sm font-semibold text-orange-700">WhiskList is being prepared</p>
                        <h2 class="mt-3 text-2xl font-semibold">Recipe discovery comes next</h2>
                        <p class="mt-4 leading-7 text-stone-600">
                            Soon you will be able to browse recipes by category and find meals using ingredients you already have.
                        </p>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
