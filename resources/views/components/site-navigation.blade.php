<nav
    @class([
        'flex flex-wrap items-center',
        'gap-1' => $context === 'header',
        'justify-center gap-x-6 gap-y-2' => $context === 'footer',
    ])
    aria-label="{{ ucfirst($context) }} navigation"
>
    @foreach ($links() as $link)
        <a
            @class([
                'rounded-lg text-sm font-medium transition',
                'px-3 py-2 hover:bg-stone-100' => $context === 'header',
                'text-stone-600 hover:text-orange-700' => $context === 'footer',
                'bg-orange-50 text-orange-800' => $context === 'header' && $link['active'],
                'text-orange-700' => $context === 'footer' && $link['active'],
                'text-stone-700' => $context === 'header' && ! $link['active'],
            ])
            href="{{ $link['url'] }}"
            @if ($link['active']) aria-current="page" @endif
        >
            {{ $link['label'] }}
        </a>
    @endforeach
</nav>
