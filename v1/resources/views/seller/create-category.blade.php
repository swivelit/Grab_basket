<x-app-layout>
    <div class="max-w-xl mx-auto py-8">
        <h2 class="text-2xl font-bold mb-6">Add Category</h2>
        <form method="POST" action="{{ route('seller.storeCategory') }}">
            @csrf
            <div class="mb-4">
                <x-input-label for="name" :value="__('Category Name')" />
                <x-text-input id="name" name="name" type="text" class="block w-full mt-1" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div class="mb-4">
                <x-input-label for="description" :value="__('Description')" />
                <textarea id="description" name="description" class="block w-full mt-1"></textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>
            <x-primary-button class="w-full">Add Category</x-primary-button>
        </form>
    </div>
</x-app-layout>
