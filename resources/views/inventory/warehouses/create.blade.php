@extends('layouts.app')

@section('title', 'Create Warehouse - AERGAS')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Create New Warehouse</h1>
            <p class="text-gray-600 mt-1">Add a new warehouse to the inventory system</p>
        </div>
        <a href="{{ route('inventory.warehouses.index') }}" class="px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Back to List
        </a>
    </div>

    <!-- Form -->
    <form action="{{ route('inventory.warehouses.store') }}" method="POST" class="bg-white rounded-xl card-shadow p-6 space-y-6">
        @csrf

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            </div>
        @endif

        <!-- Section 1: Warehouse Information -->
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <i class="fas fa-warehouse text-blue-600 text-xl"></i>
                <h2 class="text-lg font-semibold text-gray-800">Warehouse Information</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Warehouse Code <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="code" value="{{ old('code') }}" required
                           placeholder="WH-JKT-001"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 @error('code') border-red-500 @enderror">
                    @error('code')
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Warehouse Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           placeholder="Gudang Jakarta Pusat"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Warehouse Type <span class="text-red-500">*</span>
                    </label>
                    <select name="warehouse_type" required
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 @error('warehouse_type') border-red-500 @enderror">
                        <option value="">Select Type</option>
                        <option value="pusat" {{ old('warehouse_type') == 'pusat' ? 'selected' : '' }}>Gudang Pusat</option>
                        <option value="cabang" {{ old('warehouse_type') == 'cabang' ? 'selected' : '' }}>Gudang Cabang</option>
                        <option value="proyek" {{ old('warehouse_type') == 'proyek' ? 'selected' : '' }}>Gudang Proyek</option>
                    </select>
                    @error('warehouse_type')
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Status
                    </label>
                    <div class="flex items-center gap-2 mt-2">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label class="text-sm text-gray-700">Active</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Location -->
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <i class="fas fa-map-marker-alt text-green-600 text-xl"></i>
                <h2 class="text-lg font-semibold text-gray-800">Location</h2>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Address
                    </label>
                    <textarea name="address" rows="3"
                              placeholder="Full address"
                              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 @error('address') border-red-500 @enderror">{{ old('address') }}</textarea>
                    @error('address')
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            City
                        </label>
                        <input type="text" name="city" value="{{ old('city') }}"
                               placeholder="Jakarta"
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 @error('city') border-red-500 @enderror">
                        @error('city')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Province
                        </label>
                        <input type="text" name="province" value="{{ old('province') }}"
                               placeholder="DKI Jakarta"
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 @error('province') border-red-500 @enderror">
                        @error('province')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Postal Code
                        </label>
                        <input type="text" name="postal_code" value="{{ old('postal_code') }}"
                               placeholder="12345"
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 @error('postal_code') border-red-500 @enderror">
                        @error('postal_code')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Location (Short Description)
                    </label>
                    <input type="text" name="location" value="{{ old('location') }}"
                           placeholder="Jl. Sudirman No. 123"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 @error('location') border-red-500 @enderror">
                    @error('location')
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Latitude
                        </label>
                        <input type="number" name="latitude" value="{{ old('latitude') }}"
                               step="0.00000001"
                               placeholder="-6.200000"
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 @error('latitude') border-red-500 @enderror">
                        @error('latitude')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Longitude
                        </label>
                        <input type="number" name="longitude" value="{{ old('longitude') }}"
                               step="0.00000001"
                               placeholder="106.816666"
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 @error('longitude') border-red-500 @enderror">
                        @error('longitude')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: PIC Information -->
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <i class="fas fa-user-tie text-purple-600 text-xl"></i>
                <h2 class="text-lg font-semibold text-gray-800">PIC Information</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        PIC Name
                    </label>
                    <input type="text" name="pic_name" value="{{ old('pic_name') }}"
                           placeholder="Name of person in charge"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 @error('pic_name') border-red-500 @enderror">
                    @error('pic_name')
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        PIC Phone
                    </label>
                    <input type="text" name="pic_phone" value="{{ old('pic_phone') }}"
                           placeholder="081234567890"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 @error('pic_phone') border-red-500 @enderror">
                    @error('pic_phone')
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        PIC Email
                    </label>
                    <input type="email" name="pic_email" value="{{ old('pic_email') }}"
                           placeholder="pic@example.com"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 @error('pic_email') border-red-500 @enderror">
                    @error('pic_email')
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Section 4: Additional -->
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <i class="fas fa-info-circle text-orange-600 text-xl"></i>
                <h2 class="text-lg font-semibold text-gray-800">Additional Information</h2>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Description
                </label>
                <textarea name="description" rows="4"
                          placeholder="Optional description or notes about this warehouse"
                          class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                @error('description')
                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t">
            <a href="{{ route('inventory.warehouses.index') }}" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>Create Warehouse
            </button>
        </div>
    </form>
</div>
@endsection
