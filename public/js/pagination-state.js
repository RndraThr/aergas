/**
 * Pagination State Manager
 * Utility untuk menyimpan dan restore pagination state
 *
 * Usage:
 * 1. Mix ke Alpine.js component: ...PaginationStateMixin('uniqueKey')
 * 2. Tambahkan x-init="initPaginationState()" di root element
 * 3. Tambahkan @click="savePageState()" di link yang menuju detail page
 */

// Mixin untuk Alpine.js
function PaginationStateMixin(storageKey) {
    return {
        savePageState() {
            // Save current page and filters to sessionStorage
            const state = {
                page: this.pagination?.current_page || 1,
                filters: this.filters || {},
                timestamp: Date.now()
            };
            sessionStorage.setItem(storageKey, JSON.stringify(state));
        },

        restorePageState() {
            // Restore page state from sessionStorage
            const savedState = sessionStorage.getItem(storageKey);
            if (savedState) {
                try {
                    const state = JSON.parse(savedState);
                    // Only restore if saved within last 30 minutes
                    if (Date.now() - state.timestamp < 30 * 60 * 1000) {
                        // Restore pagination
                        if (this.pagination && state.page) {
                            this.pagination.current_page = state.page;
                        }

                        // Restore filters
                        if (this.filters && state.filters) {
                            Object.assign(this.filters, state.filters);
                        }

                        // Fetch with restored state
                        if (typeof this.fetchData === 'function') {
                            this.fetchData();
                        } else if (typeof this.fetchCustomers === 'function') {
                            this.fetchCustomers();
                        } else if (typeof this.fetchItems === 'function') {
                            this.fetchItems();
                        }

                        // Clear the saved state after restoring
                        sessionStorage.removeItem(storageKey);
                    }
                } catch (error) {
                    console.error('Failed to restore pagination state:', error);
                    sessionStorage.removeItem(storageKey);
                }
            }
        },

        initPaginationState() {
            // Check if we're returning from detail page
            this.restorePageState();
        },

        clearPageState() {
            sessionStorage.removeItem(storageKey);
        }
    };
}

// Standalone functions untuk vanilla JS
const PaginationStateManager = {
    save(storageKey, page, filters = {}) {
        const state = {
            page: page || 1,
            filters: filters,
            timestamp: Date.now()
        };
        sessionStorage.setItem(storageKey, JSON.stringify(state));
    },

    restore(storageKey) {
        const savedState = sessionStorage.getItem(storageKey);
        if (savedState) {
            try {
                const state = JSON.parse(savedState);
                // Only restore if saved within last 30 minutes
                if (Date.now() - state.timestamp < 30 * 60 * 1000) {
                    return state;
                }
            } catch (error) {
                console.error('Failed to restore pagination state:', error);
            }
            sessionStorage.removeItem(storageKey);
        }
        return null;
    },

    clear(storageKey) {
        sessionStorage.removeItem(storageKey);
    }
};
