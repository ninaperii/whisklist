<section id="comments" class="mt-10 scroll-mt-4">
    <h2 class="mb-4 text-2xl font-semibold">Comments <span class="text-base font-normal text-stone-600">({{ $comments->total() }})</span></h2>
    @if (session('comment_status'))
        <p class="mb-4 rounded-lg bg-green-50 p-4 text-green-800" role="status">{{ session('comment_status') }}</p>
    @endif

    @auth
        <form class="mb-6 space-y-3 rounded-2xl bg-white p-5 ring-1 ring-stone-200" method="POST" action="{{ route('recipes.comments.store', $recipe) }}">
            @csrf
            @include('recipes.comments.field', ['commentBody' => ''])
            <button class="rounded-lg bg-orange-700 px-4 py-2 font-semibold text-white" type="submit">Post comment</button>
        </form>
    @else
        <p class="mb-6 text-sm text-stone-600"><a class="font-semibold text-orange-700 hover:underline" href="{{ route('login') }}">Log in</a> to leave a comment.</p>
    @endauth

    <ul class="divide-y divide-stone-200 rounded-2xl bg-white ring-1 ring-stone-200">
        @forelse ($comments as $comment)
            <li id="comment-{{ $comment->id }}" class="scroll-mt-4 p-5 target:bg-orange-50">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <a class="inline-flex items-center gap-2 font-semibold text-orange-700 hover:underline" href="{{ route('users.show', $comment->author) }}">
                        @if ($comment->author->avatar_path)
                            <img class="size-8 shrink-0 rounded-full object-cover" src="{{ asset('storage/'.$comment->author->avatar_path) }}" alt="" loading="lazy">
                        @else
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-orange-100 text-sm text-orange-800" aria-hidden="true">{{ str($comment->author->name)->substr(0, 1)->upper() }}</span>
                        @endif
                        {{ $comment->author->name }}
                    </a>
                    <time class="text-sm text-stone-600" datetime="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->format('M j, Y') }}</time>
                </div>
                <p class="mt-2 whitespace-pre-wrap break-words text-stone-700">{{ $comment->body }}</p>
                @can('update', $comment)
                    <div class="mt-3 flex items-center gap-4 text-sm font-semibold">
                        <a class="text-orange-700 hover:underline" href="{{ route('recipes.comments.edit', [$recipe, $comment]) }}">Edit</a>
                        <form method="POST" action="{{ route('recipes.comments.destroy', [$recipe, $comment]) }}" data-confirm-delete="Delete this comment permanently?">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-700 hover:underline" type="submit">Delete</button>
                        </form>
                    </div>
                @endcan
            </li>
        @empty
            <li class="p-5 text-stone-600">No comments on this page.</li>
        @endforelse
    </ul>
    <div class="mt-5">{{ $comments->links() }}</div>
</section>
