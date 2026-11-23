<header class="border-b border-stone-200 bg-white">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-5">
            <a class="text-2xl font-semibold tracking-tight text-orange-700" href="{{ route('home') }}">
                WhiskList
            </a>
            <x-site-navigation />
        </div>

        <div class="flex items-center gap-3">
            @guest
                <a class="rounded-lg px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100" href="{{ route('login') }}">
                    Log in
                </a>
                <a class="rounded-lg bg-orange-700 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-800" href="{{ route('register') }}">
                    Register
                </a>
            @else
                <span class="hidden text-sm text-stone-600 md:inline">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-100" type="submit">
                        Log out
                    </button>
                </form>
            @endguest
        </div>
    </div>
</header>
