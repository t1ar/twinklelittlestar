<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            New post in c/{{ $community->name }}
        </h2>
    </x-slot>

    <div class="py-8 max-w-2xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('posts.store', $community) }}"
              class="bg-white p-6 rounded-lg shadow-sm space-y-4">
            @csrf

            <div>
                <x-input-label for="title" value="Title" />
                <x-text-input id="title" name="title" class="block mt-1 w-full" :value="old('title')" required autofocus />
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="body" value="Text (optional if you add a link)" />
                <textarea id="body" name="body" rows="6"
                          class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">{{ old('body') }}</textarea>
                <x-input-error :messages="$errors->get('body')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="url" value="Link (optional)" />
                <x-text-input id="url" name="url" type="url" class="block mt-1 w-full" :value="old('url')" />
                <x-input-error :messages="$errors->get('url')" class="mt-2" />
            </div>

            <x-primary-button>Post</x-primary-button>
        </form>
    </div>
</x-app-layout>