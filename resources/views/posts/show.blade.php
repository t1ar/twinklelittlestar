<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('communities.show', $post->community) }}"
           class="text-sm text-indigo-600 hover:underline">
            c/{{ $post->community->name }}
        </a>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <article class="bg-white p-6 rounded-lg shadow-sm">
            <h1 class="text-2xl font-bold text-gray-900">{{ $post->title }}</h1>
            <p class="text-xs text-gray-400 mt-1">
                by {{ $post->user->name }} · {{ $post->created_at->diffForHumans() }}
            </p>

            @if ($post->url)
                <a href="{{ $post->url }}" target="_blank" rel="noopener noreferrer"
                   class="block mt-4 text-indigo-600 hover:underline break-all">
                    {{ $post->url }}
                </a>
            @endif

            @if ($post->body)
                <div class="mt-4 text-gray-800 whitespace-pre-line">{{ $post->body }}</div>
            @endif

            @auth
                @if (auth()->id() === $post->user_id)
                    <form method="POST" action="{{ route('posts.destroy', $post) }}"
                          class="mt-6" onsubmit="return confirm('Delete this post?')">
                        @csrf @method('DELETE')
                        <button class="text-sm text-red-600 hover:underline">Delete</button>
                    </form>
                @endif
            @endauth
        </article>

        <section class="bg-white p-6 rounded-lg shadow-sm">
    <h2 class="font-semibold text-lg mb-3">
        {{ $byParent->flatten()->count() }} comments
    </h2>

    @auth
        <form method="POST" action="{{ route('comments.store', $post) }}" class="space-y-2">
            @csrf
            <textarea name="body" rows="3" required placeholder="Write a comment..."
                      class="w-full border-gray-300 rounded-md shadow-sm">{{ old('body') }}</textarea>
            <x-input-error :messages="$errors->get('body')" />
            <x-primary-button>Comment</x-primary-button>
        </form>
    @else
        <p class="text-sm text-gray-500">
            <a href="{{ route('login') }}" class="text-indigo-600 underline">Log in</a> to comment.
        </p>
    @endauth

    @foreach ($byParent->get(null, []) as $comment)
        @include('comments._comment', ['comment' => $comment])
    @endforeach
</section>
    </div>
</x-app-layout>