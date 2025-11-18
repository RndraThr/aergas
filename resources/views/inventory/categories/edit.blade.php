@extends('layouts.app')

@section('title', 'Edit Category - AERGAS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Edit Category</h1>
            <p class="text-gray-600 mt-1">Update category information</p>
        </div>
        <a href="{{ route('inventory.categories.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Back to Categories
        </a>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <form action="{{ route('inventory.categories.update', $category) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <!-- Code -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                        Code <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="code" id="code" value="{{ old('code', $category->code) }}" required
                           class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('code') border-red-500 @enderror"
                           placeholder="e.g., CAT001">
                    @error('code')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" required
                           class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                           placeholder="e.g., Pipes & Fittings">
                    @error('name')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Parent Category -->
                <div>
                    <label for="parent_category_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Parent Category <span class="text-gray-400 text-xs">(Optional)</span>
                    </label>
                    <select name="parent_category_id" id="parent_category_id"
                            class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('parent_category_id') border-red-500 @enderror">
                        <option value="">-- None (Root Category) --</option>
                        @foreach($categories ?? [] as $parentCategory)
                            @if($parentCategory->id !== $category->id)
                                <option value="{{ $parentCategory->id }}"
                                    {{ old('parent_category_id', $category->parent_category_id) == $parentCategory->id ? 'selected' : '' }}>
                                    {{ $parentCategory->name }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('parent_category_id')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">Select a parent category to create a subcategory</p>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        Description <span class="text-gray-400 text-xs">(Optional)</span>
                    </label>
                    <textarea name="description" id="description" rows="4"
                              class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror"
                              placeholder="Brief description of this category...">{{ old('description', $category->description) }}</textarea>
                    @error('description')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-3 mt-6 pt-6 border-t border-gray-200">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Update Category
                </button>
                <a href="{{ route('inventory.categories.index') }}" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
