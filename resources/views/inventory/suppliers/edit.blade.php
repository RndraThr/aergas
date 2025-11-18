@extends('layouts.app')

@section('title', 'Edit Supplier - AERGAS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Edit Supplier</h1>
            <p class="text-gray-600 mt-1">Update supplier information</p>
        </div>
        <a href="{{ route('inventory.suppliers.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Back to Suppliers
        </a>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <form action="{{ route('inventory.suppliers.update', $supplier) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Supplier Information -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                    <i class="fas fa-building text-blue-600 mr-2"></i>Supplier Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                            Supplier Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="code" id="code" value="{{ old('code', $supplier->code) }}" required
                               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('code') border-red-500 @enderror"
                               placeholder="e.g., SUP001">
                        @error('code')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            Supplier Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name', $supplier->name) }}" required
                               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                               placeholder="e.g., PT Supplier Indonesia">
                        @error('name')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                    <i class="fas fa-phone text-green-600 mr-2"></i>Contact Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-1">
                            Contact Person <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <input type="text" name="contact_person" id="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}"
                               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('contact_person') border-red-500 @enderror"
                               placeholder="John Doe">
                        @error('contact_person')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                            Phone <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $supplier->phone) }}"
                               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('phone') border-red-500 @enderror"
                               placeholder="021-12345678">
                        @error('phone')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            Email <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <input type="email" name="email" id="email" value="{{ old('email', $supplier->email) }}"
                               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('email') border-red-500 @enderror"
                               placeholder="supplier@example.com">
                        @error('email')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Address -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                    <i class="fas fa-map-marker-alt text-red-600 mr-2"></i>Address
                </h2>
                <div class="space-y-4">
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-1">
                            Address <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <textarea name="address" id="address" rows="3"
                                  class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('address') border-red-500 @enderror"
                                  placeholder="Full address...">{{ old('address', $supplier->address) }}</textarea>
                        @error('address')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700 mb-1">
                                City <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <input type="text" name="city" id="city" value="{{ old('city', $supplier->city) }}"
                                   class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('city') border-red-500 @enderror"
                                   placeholder="Jakarta">
                            @error('city')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="province" class="block text-sm font-medium text-gray-700 mb-1">
                                Province <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <input type="text" name="province" id="province" value="{{ old('province', $supplier->province) }}"
                                   class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('province') border-red-500 @enderror"
                                   placeholder="DKI Jakarta">
                            @error('province')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">
                                Postal Code <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $supplier->postal_code) }}"
                                   class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('postal_code') border-red-500 @enderror"
                                   placeholder="12345">
                            @error('postal_code')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Information -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                    <i class="fas fa-dollar-sign text-orange-600 mr-2"></i>Financial Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="npwp" class="block text-sm font-medium text-gray-700 mb-1">
                            NPWP <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <input type="text" name="npwp" id="npwp" value="{{ old('npwp', $supplier->npwp) }}"
                               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('npwp') border-red-500 @enderror"
                               placeholder="00.000.000.0-000.000">
                        @error('npwp')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="payment_terms" class="block text-sm font-medium text-gray-700 mb-1">
                            Payment Terms <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <select name="payment_terms" id="payment_terms"
                                class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('payment_terms') border-red-500 @enderror">
                            <option value="">-- Select Payment Terms --</option>
                            <option value="COD" {{ old('payment_terms', $supplier->payment_terms) == 'COD' ? 'selected' : '' }}>COD (Cash on Delivery)</option>
                            <option value="Net 7" {{ old('payment_terms', $supplier->payment_terms) == 'Net 7' ? 'selected' : '' }}>Net 7 Days</option>
                            <option value="Net 14" {{ old('payment_terms', $supplier->payment_terms) == 'Net 14' ? 'selected' : '' }}>Net 14 Days</option>
                            <option value="Net 30" {{ old('payment_terms', $supplier->payment_terms) == 'Net 30' ? 'selected' : '' }}>Net 30 Days</option>
                            <option value="Net 45" {{ old('payment_terms', $supplier->payment_terms) == 'Net 45' ? 'selected' : '' }}>Net 45 Days</option>
                            <option value="Net 60" {{ old('payment_terms', $supplier->payment_terms) == 'Net 60' ? 'selected' : '' }}>Net 60 Days</option>
                        </select>
                        @error('payment_terms')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="bank_account_name" class="block text-sm font-medium text-gray-700 mb-1">
                            Bank Account Name <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <input type="text" name="bank_account_name" id="bank_account_name" value="{{ old('bank_account_name', $supplier->bank_account_name) }}"
                               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('bank_account_name') border-red-500 @enderror"
                               placeholder="PT Supplier Indonesia">
                        @error('bank_account_name')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="bank_account_number" class="block text-sm font-medium text-gray-700 mb-1">
                            Bank Account Number <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <input type="text" name="bank_account_number" id="bank_account_number" value="{{ old('bank_account_number', $supplier->bank_account_number) }}"
                               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('bank_account_number') border-red-500 @enderror"
                               placeholder="1234567890">
                        @error('bank_account_number')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Additional Notes -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                    <i class="fas fa-sticky-note text-purple-600 mr-2"></i>Additional Notes
                </h2>
                <div class="space-y-4">
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                            Notes <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <textarea name="notes" id="notes" rows="3"
                                  class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('notes') border-red-500 @enderror"
                                  placeholder="Additional information about the supplier...">{{ old('notes', $supplier->notes) }}</textarea>
                        @error('notes')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $supplier->is_active) ? 'checked' : '' }}
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">
                            Supplier is Active
                        </label>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-3 pt-6 border-t border-gray-200">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Update Supplier
                </button>
                <a href="{{ route('inventory.suppliers.index') }}" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
