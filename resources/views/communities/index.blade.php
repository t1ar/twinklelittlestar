<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Communities</h2>
            @auth
                <a href="{{ route('communities.create') }}"
                   class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-500">
                    Create community
                </a>
            @endauth
        </div>
    </x-slot>

    <div class="py-8 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @forelse ($communities as $community)
            <a href="{{ route('communities.show', $community) }}"
               class="block bg-white p-4 rounded-lg shadow-sm hover:shadow-md transition">
                <div class="font-semibold text-gray-900">c/{{ $community->name }}</div>
                <p class="text-sm text-gray-600">{{ $community->description }}</p>
                <p class="text-xs text-gray-400 mt-1">
                    {{ $community->members_count }} members · {{ $community->posts_count }} posts
                </p>
            </a>
        @empty
            <p class="text-gray-500">No communities yet. Be the first to create one!</p>
        @endforelse

        {{ $communities->links() }}
    </div>
</x-app-layout>