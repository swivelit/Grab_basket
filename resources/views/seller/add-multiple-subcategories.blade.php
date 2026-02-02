<x-app-layout>
    <div class="max-w-xl mx-auto py-8">
        <h2 class="text-2xl font-bold mb-6">Add Multiple Subcategories</h2>
        <form method="POST" action="{{ route('seller.storeMultipleSubcategories') }}">
            @csrf
            <div class="mb-4">
                <x-input-label for="category_id" :value="__('Select Category')" />
                <select id="category_id" name="category_id" class="block w-full mt-1" required>
                    <option value="">Select Category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }} (ID: {{ $category->unique_id }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
            </div>
            <div id="subcategories-wrapper">
                <div class="mb-4 subcategory-group">
                    <x-input-label for="subcategory_names[]" :value="__('Subcategory Name')" />
                    <x-text-input name="subcategory_names[]" type="text" class="block w-full mt-1 text-uppercase" style="text-transform:uppercase;" required />
                    <x-input-error :messages="$errors->get('subcategory_names.0')" class="mt-2" />
                </div>
            </div>
            <button type="button" class="btn btn-secondary mb-4" onclick="addSubcategoryField()">Add Another Subcategory</button>
            <x-primary-button class="w-full">Add Subcategories</x-primary-button>
        </form>
    </div>
    <script>
        function addSubcategoryField() {
            var wrapper = document.getElementById('subcategories-wrapper');
            var group = document.createElement('div');
            group.className = 'mb-4 subcategory-group';
            group.innerHTML = `<x-input-label for='subcategory_names[]' value='Subcategory Name' />
                <input name='subcategory_names[]' type='text' class='block w-full mt-1 text-uppercase' style='text-transform:uppercase;' required />`;
            wrapper.appendChild(group);
        }
    </script>
</x-app-layout>
