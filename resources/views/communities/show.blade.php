<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">c/{{ $community->name }}</h2>
                <p class="text-sm text-gray-500">{{ $community->description }}</p>
            </div>

            @auth
                @if ($isMember)
                    <form method="POST" action="{{ route('communities.leave', $community) }}">
                        @csrf @method('DELETE')
                        <button class="px-4 py-2 border border-gray-300 text-sm rounded-md hover:bg-gray-50">Leave</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('communities.join', $community) }}">
                        @csrf
                        <button class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-500">Join</button>
                    </form>
                @endif
                 <a href="{{ route('posts.create', $community) }}"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-500">
                    New post
                </a>
            @endauth
        </div>
    </x-slot>

    <div class="py-8 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @forelse ($posts as $post)
            <a href="{{ route('posts.show', $post) }}"
               class="block bg-white p-4 rounded-lg shadow-sm hover:bg-gray-50">
                <div class="font-semibold">{{ $post->title }}</div>
                <p class="text-xs text-gray-400">by {{ $post->user->name }} · {{ $post->created_at->diffForHumans() }}</p>
            </a>
        @empty
            <p class="text-gray-500">No posts yet.</p>
        @endforelse

        {{ $posts->links() }}
    </div>
</x-app-layout>