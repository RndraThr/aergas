/**
 * Alpine.js Pagination Mixin
 *
 * Provides reusable pagination functionality for Alpine.js components
 *
 * Usage:
 * function myComponent() {
 *     return {
 *         ...paginationMixin(),
 *         // your other component data and methods
 *     }
 * }
 */

export function paginationMixin(initialPagination = {}) {
    return {
        pagination: {
            current_page: initialPagination.current_page || 1,
            last_page: initialPagination.last_page || 1,
            per_page: initialPagination.per_page || 15,
            total: initialPagination.total || 0,
            from: initialPagination.from || 0,
            to: initialPagination.to || 0
        },

        /**
         * Computed property for pagination pages
         * Returns array of page numbers to display
         */
        get paginationPages() {
            const pages = [];
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;

            // Show max 5 pages: current ± 2 pages
            let start = Math.max(1, current - 2);
            let end = Math.min(last, current + 2);

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            return pages;
        },

        /**
         * Navigate to specific page
         */
        goToPage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.pagination.current_page = page;
                if (typeof this.fetchData === 'function') {
                    this.fetchData();
                }
            }
        },

        /**
         * Go to previous page
         */
        previousPage() {
            if (this.pagination.current_page > 1) {
                this.pagination.current_page--;
                if (typeof this.fetchData === 'function') {
                    this.fetchData();
                }
            }
        },

        /**
         * Go to next page
         */
        nextPage() {
            if (this.pagination.current_page < this.pagination.last_page) {
                this.pagination.current_page++;
                if (typeof this.fetchData === 'function') {
                    this.fetchData();
                }
            }
        },

        /**
         * Update pagination data from server response
         */
        updatePagination(paginationData) {
            this.pagination = {
                current_page: paginationData.current_page || 1,
                last_page: paginationData.last_page || 1,
                per_page: paginationData.per_page || 15,
                total: paginationData.total || 0,
                from: paginationData.from || 0,
                to: paginationData.to || 0
            };
        }
    };
}

// Make it available globally for non-module scripts
if (typeof window !== 'undefined') {
    window.paginationMixin = paginationMixin;
}
