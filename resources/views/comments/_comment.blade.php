<div class="mt-4 pl-4 border-l-2 border-gray-200">
    <p class="text-xs text-gray-400">
        {{ $comment->user->name }} · {{ $comment->created_at->diffForHumans() }}
    </p>
    <div class="text-gray-800 whitespace-pre-line">{{ $comment->body }}</div>

    @auth
        <div class="flex gap-4 mt-1 text-xs">
            <details>
                <summary class="cursor-pointer text-indigo-600">Reply</summary>
                <form method="POST" action="{{ route('comments.store', $post) }}" class="mt-2 space-y-2">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                    <textarea name="body" rows="2" required
                              class="w-full border-gray-300 rounded-md shadow-sm text-sm"></textarea>
                    <x-primary-button>Reply</x-primary-button>
                </form>
            </details>

            @if (auth()->id() === $comment->user_id)
                <form method="POST" action="{{ route('comments.destroy', $comment) }}"
                      onsubmit="return confirm('Delete this comment?')">
                    @csrf @method('DELETE')
                    <button class="text-red-600">Delete</button>
                </form>
            @endif
        </div>
    @endauth

    {{-- the recursion: render this comment's replies with this same file --}}
    @foreach ($byParent->get($comment->id, []) as $reply)
        @include('comments._comment', ['comment' => $reply])
    @endforeach
</div>