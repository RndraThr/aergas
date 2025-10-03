{{--
    Alpine.js Pagination Component

    Usage:
    <x-pagination />

    Requirements:
    - Alpine.js data must have `pagination` object with: current_page, last_page, per_page, total, from, to
    - Alpine.js must have methods: goToPage(page), previousPage(), nextPage()
    - Alpine.js must have computed property: paginationPages
--}}

<div x-show="pagination.total > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
    <div class="flex items-center justify-between">
        {{-- Info: Showing X to Y of Z results --}}
        <div class="flex items-center">
            <span class="text-sm text-gray-700">
                Showing
                <span class="font-medium" x-text="pagination.from || 0"></span>
                to
                <span class="font-medium" x-text="pagination.to || 0"></span>
                of
                <span class="font-medium" x-text="pagination.total || 0"></span>
                results
            </span>
        </div>

        {{-- Navigation Buttons --}}
        <div class="flex items-center space-x-2">
            {{-- Previous Button --}}
            <button @click="previousPage()"
                    :disabled="pagination.current_page <= 1"
                    :class="pagination.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                    class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 transition-colors">
                Previous
            </button>

            {{-- Page Numbers (dynamic) --}}
            <template x-for="page in paginationPages" :key="page">
                <button @click="goToPage(page)"
                        :class="page === pagination.current_page ? 'bg-aergas-orange text-white' : 'text-gray-700 hover:bg-gray-100'"
                        class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium transition-colors">
                    <span x-text="page"></span>
                </button>
            </template>

            {{-- Next Button --}}
            <button @click="nextPage()"
                    :disabled="pagination.current_page >= pagination.last_page"
                    :class="pagination.current_page >= pagination.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                    class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 transition-colors">
                Next
            </button>
        </div>
    </div>
</div>
