@if ($paginator->hasPages())
    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
        <div class="flex items-center justify-between">
            {{-- Info: Showing X to Y of Z results --}}
            <div class="flex items-center">
                <span class="text-sm text-gray-700">
                    Showing
                    <span class="font-medium">{{ $paginator->firstItem() ?? 0 }}</span>
                    to
                    <span class="font-medium">{{ $paginator->lastItem() ?? 0 }}</span>
                    of
                    <span class="font-medium">{{ $paginator->total() }}</span>
                    results
                </span>
            </div>

            {{-- Navigation Buttons --}}
            <div class="flex items-center space-x-2">
                {{-- Previous Button --}}
                @if ($paginator->onFirstPage())
                    <button disabled class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 opacity-50 cursor-not-allowed transition-colors">
                        Previous
                    </button>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                        Previous
                    </a>
                @endif

                {{-- Page Numbers --}}
                @php
                    $currentPage = $paginator->currentPage();
                    $lastPage = $paginator->lastPage();

                    // Calculate start and end page numbers (same logic as Alpine)
                    $start = max(1, $currentPage - 2);
                    $end = min($lastPage, $currentPage + 2);

                    // Adjust if we're near the beginning or end
                    if ($end - $start < 4) {
                        if ($start === 1) {
                            $end = min($lastPage, $start + 4);
                        } else if ($end === $lastPage) {
                            $start = max(1, $end - 4);
                        }
                    }
                @endphp

                @for ($page = $start; $page <= $end; $page++)
                    @if ($page == $currentPage)
                        <button class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium bg-aergas-orange text-white transition-colors">
                            {{ $page }}
                        </button>
                    @else
                        <a href="{{ $paginator->url($page) }}" class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                            {{ $page }}
                        </a>
                    @endif
                @endfor

                {{-- Next Button --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                        Next
                    </a>
                @else
                    <button disabled class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 opacity-50 cursor-not-allowed transition-colors">
                        Next
                    </button>
                @endif
            </div>
        </div>
    </div>
@endif
