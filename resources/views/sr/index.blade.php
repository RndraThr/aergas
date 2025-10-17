{{-- resources/views/sr/index.blade.php - UPDATED WITH ALPINE PAGINATION --}}
@extends('layouts.app')

@section('title', 'Data SR - AERGAS')

@section('content')
<div class="space-y-6" x-data="srIndexData()" x-init="init()">

  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-bold text-gray-800">Data SR</h1>
      <p class="text-gray-600 mt-1">Daftar Service Request</p>
    </div>
    <div class="flex gap-2">
      <button @click="fetchData()" class="px-3 py-2 bg-gray-100 rounded hover:bg-gray-200">
        <i class="fas fa-sync-alt mr-1"></i>Refresh
      </button>
      <a href="{{ route('sr.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
        <i class="fas fa-plus mr-2"></i>Buat SR
      </a>
    </div>
  </div>

  {{-- Filters --}}
  <div class="bg-white p-4 rounded-xl card-shadow">
    <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
      <div class="md:col-span-4">
        <input type="text" x-model="filters.q" @input.debounce.500ms="fetchData(true)"
               placeholder="Cari Reff ID, Customer, atau Status..."
               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <select x-model="filters.module_status" @change="fetchData(true)"
                class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
          <option value="">Semua Status</option>
          <option value="draft">Draft</option>
          <option value="ai_validation">AI Validation</option>
          <option value="tracer_review">Tracer Review</option>
          <option value="cgp_review">CGP Review</option>
          <option value="completed">Completed</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>
      <div>
        <button @click="resetFilters()" class="w-full px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">
          <i class="fas fa-times mr-1"></i>Reset
        </button>
      </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
      <div>
        <label class="block text-xs text-gray-600 mb-1">Tanggal Instalasi Dari</label>
        <input type="date" x-model="filters.tanggal_dari" @change="fetchData(true)"
               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-xs text-gray-600 mb-1">Tanggal Instalasi Sampai</label>
        <input type="date" x-model="filters.tanggal_sampai" @change="fetchData(true)"
               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
      </div>
      <div class="flex items-end">
        <button @click="resetDateFilter()" x-show="filters.tanggal_dari || filters.tanggal_sampai"
                class="w-full px-4 py-2 bg-red-100 text-red-700 rounded hover:bg-red-200">
          <i class="fas fa-times mr-1"></i>Reset Filter Tanggal
        </button>
      </div>
    </div>
  </div>

  {{-- Stats Cards --}}
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white p-4 rounded-lg card-shadow">
      <div class="text-2xl font-bold text-blue-600" x-text="stats.total"></div>
      <div class="text-sm text-gray-600">Total SR</div>
    </div>
    <div class="bg-white p-4 rounded-lg card-shadow">
      <div class="text-2xl font-bold text-yellow-600" x-text="stats.draft"></div>
      <div class="text-sm text-gray-600">Draft</div>
    </div>
    <div class="bg-white p-4 rounded-lg card-shadow">
      <div class="text-2xl font-bold text-purple-600" x-text="stats.ready"></div>
      <div class="text-sm text-gray-600">Ready for Review</div>
    </div>
    <div class="bg-white p-4 rounded-lg card-shadow">
      <div class="text-2xl font-bold text-green-600" x-text="stats.completed"></div>
      <div class="text-sm text-gray-600">Completed</div>
    </div>
  </div>

  {{-- Loading State --}}
  <div x-show="loading" class="bg-white rounded-xl card-shadow p-8 text-center">
    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
    <p class="text-gray-500 mt-4">Loading data SR...</p>
  </div>

  {{-- Table --}}
  <div x-show="!loading" class="bg-white rounded-xl card-shadow overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reff ID</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Foto</th>
          <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <template x-for="row in items" :key="row.id">
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.id"></td>
            <td class="px-4 py-3 text-sm font-medium text-blue-600">
              <a :href="`/sr/${row.id}`" class="hover:text-blue-800" x-text="row.reff_id_pelanggan"></a>
            </td>
            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.calon_pelanggan?.nama_pelanggan || '-'"></td>
            <td class="px-4 py-3 text-sm text-gray-700">
              <template x-if="row.created_by">
                <div class="flex items-center">
                  <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mr-2">
                    <span class="text-xs font-medium text-blue-600" x-text="row.created_by.name?.charAt(0).toUpperCase()"></span>
                  </div>
                  <span class="text-sm" x-text="row.created_by.name"></span>
                </div>
              </template>
              <template x-if="!row.created_by">
                <span class="text-gray-400 text-sm">-</span>
              </template>
            </td>
            <td class="px-4 py-3 text-sm text-gray-700" x-text="formatDate(row.tanggal_pemasangan)"></td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <span class="px-2 py-1 text-xs font-medium rounded-full"
                      :class="getStatusClass(row.module_status || row.status)"
                      x-text="getStatusText(row.module_status || row.status)"></span>
                <template x-if="row.rejected_photos_count > 0">
                  <div class="relative">
                    <button class="text-red-600 hover:text-red-800 text-xs flex items-center gap-1"
                            :data-sr-id="row.id"
                            @mouseenter="showRejectionPopup(row.id, $event.target)"
                            @mouseleave="hideRejectionPopup(row.id)">
                      <i class="fas fa-exclamation-circle"></i>
                      <span class="font-medium" x-text="`(${row.rejected_photos_count})`"></span>
                    </button>
                  </div>
                </template>
              </div>
            </td>
            <td class="px-4 py-3">
              <div class="space-y-0.5">
                <template x-if="row.photo_status_details && row.photo_status_details.length > 0">
                  <template x-for="photo in row.photo_status_details" :key="photo.field">
                    <div class="flex items-center gap-1.5 text-xs" :class="photo.bg + ' rounded px-2 py-0.5'">
                      <i class="fas" :class="[photo.icon, photo.color]"></i>
                      <span class="font-medium" :class="photo.color" x-text="photo.label"></span>
                      <template x-if="photo.status === 'missing'">
                        <span class="text-red-500 text-xs">(Kosong)</span>
                      </template>
                      <template x-if="photo.status === 'corrupted'">
                        <span class="text-yellow-600 text-xs">(Error)</span>
                      </template>
                    </div>
                  </template>
                </template>
                <template x-if="!row.photo_status_details || row.photo_status_details.length === 0">
                  <span class="text-gray-400 text-xs">No photo data</span>
                </template>
              </div>
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex justify-end gap-1">
                <a :href="`/sr/${row.id}`"
                   @click="savePageState()"
                   class="px-3 py-1.5 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200"
                   title="Lihat Detail">
                  <i class="fas fa-eye mr-1"></i>Detail
                </a>
                <template x-if="canEdit(row)">
                  <a :href="`/sr/${row.id}/edit`"
                     @click="savePageState()"
                     class="px-3 py-1.5 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200"
                     title="Edit SR">
                    <i class="fas fa-edit mr-1"></i>Edit
                  </a>
                </template>
                <template x-if="canEdit(row)">
                  <button @click="confirmDelete(row.id, row.reff_id_pelanggan)"
                          class="px-3 py-1.5 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200"
                          title="Hapus SR">
                    <i class="fas fa-trash mr-1"></i>Hapus
                  </button>
                </template>
              </div>
            </td>
          </tr>
        </template>

        {{-- Empty State --}}
        <tr x-show="items.length === 0">
          <td colspan="8" class="px-4 py-8 text-center text-gray-500">
            <div class="flex flex-col items-center">
              <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
              <p class="text-lg font-medium mb-1">Belum ada data SR</p>
              <p class="text-sm">Silakan buat SR baru untuk memulai</p>
              <a href="{{ route('sr.create') }}" class="mt-3 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Buat SR Pertama
              </a>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  <div x-show="!loading && pagination.total > 0">
    <x-pagination />
  </div>
</div>

{{-- Rejection Popup --}}
<div id="rejection-popup-container" class="hidden fixed w-96 bg-white border border-red-200 rounded-lg shadow-xl z-[9999] max-h-96 overflow-y-auto"
     onmouseenter="keepPopupOpen(window.currentSrId)"
     onmouseleave="hideRejectionPopup(window.currentSrId)">
  <div class="sticky top-0 bg-red-50 px-3 py-2 border-b border-red-200">
    <h3 class="text-xs font-semibold text-red-800">Rejection Details</h3>
  </div>
  <div id="rejection-popup-content" class="p-3">
    <!-- Content loaded via JS -->
  </div>
</div>

{{-- Delete Modal (unchanged, kept for consistency) --}}
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center transition-opacity duration-300">
  <div id="deleteModalContent" class="bg-white rounded-xl p-6 max-w-md w-full mx-4 shadow-2xl transform transition-all duration-300 scale-95 opacity-0">
    <div class="flex items-center mb-4">
      <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
      </div>
      <div>
        <h3 class="text-lg font-semibold text-gray-900">Konfirmasi Hapus</h3>
        <p class="text-sm text-gray-600">Tindakan ini tidak dapat dibatalkan</p>
      </div>
    </div>

    <div class="mb-6">
      <p class="text-gray-700">
        Apakah Anda yakin ingin menghapus SR dengan Reff ID:
        <span id="deleteReffId" class="font-semibold text-red-600"></span>?
      </p>
      <p class="text-sm text-gray-500 mt-2">
        Semua data terkait termasuk foto dan approval history akan terhapus permanent.
      </p>
    </div>

    <div class="flex gap-3">
      <button onclick="closeDeleteModal()"
              class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
        <i class="fas fa-times mr-2"></i>Batal
      </button>
      <button id="deleteButton" onclick="executeDelete()"
              class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
        <i class="fas fa-trash mr-2"></i>Hapus
      </button>
    </div>
  </div>
</div>

@push('scripts')
<script>
function srIndexData() {
    return {
        items: @json($sr->items() ?? []),
        pagination: {
            current_page: @json($sr->currentPage() ?? 1),
            last_page: @json($sr->lastPage() ?? 1),
            per_page: @json($sr->perPage() ?? 15),
            total: @json($sr->total() ?? 0),
            from: @json($sr->firstItem() ?? 0),
            to: @json($sr->lastItem() ?? 0)
        },
        filters: {
            q: '{{ request("q") }}',
            module_status: '{{ request("module_status") }}',
            tanggal_dari: '{{ request("tanggal_dari") }}',
            tanggal_sampai: '{{ request("tanggal_sampai") }}'
        },
        stats: {
            total: {{ $sr->total() ?? 0 }},
            draft: {{ $sr->where('module_status', 'draft')->count() ?? 0 }},
            ready: {{ $sr->where('module_status', 'tracer_review')->count() ?? 0 }},
            completed: {{ $sr->where('module_status', 'completed')->count() ?? 0 }}
        },
        loading: false,

        async fetchData(resetPage = false) {
            // Auto-reset to page 1 when filters change
            if (resetPage) {
                this.pagination.current_page = 1;
            }

            this.loading = true;

            try {
                const params = new URLSearchParams({
                    q: this.filters.q,
                    module_status: this.filters.module_status,
                    tanggal_dari: this.filters.tanggal_dari,
                    tanggal_sampai: this.filters.tanggal_sampai,
                    page: this.pagination.current_page,
                    ajax: 1
                });

                const response = await fetch(`{{ route('sr.index') }}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.items = data.data.data || [];
                    this.pagination = {
                        current_page: data.data.current_page,
                        last_page: data.data.last_page,
                        per_page: data.data.per_page,
                        total: data.data.total,
                        from: data.data.from,
                        to: data.data.to
                    };
                    this.stats = data.stats || this.stats;

                    // Smart pagination: if current page > last page, auto-adjust
                    if (this.pagination.current_page > this.pagination.last_page && this.pagination.last_page > 0) {
                        this.pagination.current_page = this.pagination.last_page;
                        this.fetchData(); // Re-fetch with adjusted page
                        return;
                    }
                }
            } catch (error) {
                console.error('Error fetching SR data:', error);
            } finally {
                this.loading = false;
            }
        },

        resetFilters() {
            this.filters = {
                q: '',
                module_status: '',
                tanggal_dari: '',
                tanggal_sampai: ''
            };
            this.fetchData(true);
        },

        resetDateFilter() {
            this.filters.tanggal_dari = '';
            this.filters.tanggal_sampai = '';
            this.fetchData(true);
        },

        formatDate(date) {
            if (!date) return '-';
            const d = new Date(date);
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
        },

        getStatusClass(status) {
            const classes = {
                'not_started': 'bg-gray-100 text-gray-700',
                'draft': 'bg-gray-100 text-gray-700',
                'ai_validation': 'bg-purple-100 text-purple-800',
                'tracer_review': 'bg-blue-100 text-blue-800',
                'cgp_review': 'bg-yellow-100 text-yellow-800',
                'completed': 'bg-green-100 text-green-800',
                'rejected': 'bg-red-100 text-red-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-700';
        },

        getStatusText(status) {
            const statusMap = {
                'not_started': 'Not Started',
                'draft': 'Draft',
                'ai_validation': 'AI Validation',
                'tracer_review': 'Tracer Review',
                'cgp_review': 'CGP Review',
                'completed': 'Completed',
                'rejected': 'Rejected'
            };
            return statusMap[status] || status?.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        },

        canEdit(row) {
            const displayStatus = row.module_status || row.status;
            return ['draft', 'ai_validation', 'tracer_review', 'rejected'].includes(displayStatus);
        },

        confirmDelete(id, reffId) {
            window.deleteId = id;
            document.getElementById('deleteReffId').textContent = reffId;
            showDeleteModal();
        },

        // Pagination methods
        get paginationPages() {
            const pages = [];
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;

            let start = Math.max(1, current - 2);
            let end = Math.min(last, current + 2);

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            return pages;
        },

        goToPage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.pagination.current_page = page;
                this.fetchData();
            }
        },

        previousPage() {
            if (this.pagination.current_page > 1) {
                this.pagination.current_page--;
                this.fetchData();
            }
        },

        nextPage() {
            if (this.pagination.current_page < this.pagination.last_page) {
                this.pagination.current_page++;
                this.fetchData();
            }
        },

        savePageState() {
            // Save current page and filters to sessionStorage
            const state = {
                page: this.pagination.current_page,
                filters: this.filters,
                timestamp: Date.now()
            };
            sessionStorage.setItem('srIndexPageState', JSON.stringify(state));
        },

        restorePageState() {
            // Restore page state from sessionStorage
            const savedState = sessionStorage.getItem('srIndexPageState');
            if (savedState) {
                const state = JSON.parse(savedState);
                // Only restore if saved within last 30 minutes
                if (Date.now() - state.timestamp < 30 * 60 * 1000) {
                    this.pagination.current_page = state.page || 1;
                    this.filters = state.filters || this.filters;
                    this.fetchData();
                    // Clear the saved state after restoring
                    sessionStorage.removeItem('srIndexPageState');
                }
            }
        },

        init() {
            // Check if we're returning from detail page
            this.restorePageState();
        }
    }
}

// Delete modal functions (kept from original)
let deleteId = null;
let isDeleting = false;

function showDeleteModal() {
  const modal = document.getElementById('deleteModal');
  const modalContent = document.getElementById('deleteModalContent');
  modal.classList.remove('hidden');
  setTimeout(() => {
    modalContent.classList.remove('scale-95', 'opacity-0');
    modalContent.classList.add('scale-100', 'opacity-100');
  }, 10);
}

function closeDeleteModal() {
  if (isDeleting) return;
  const modal = document.getElementById('deleteModal');
  const modalContent = document.getElementById('deleteModalContent');
  modalContent.classList.remove('scale-100', 'opacity-100');
  modalContent.classList.add('scale-95', 'opacity-0');
  setTimeout(() => {
    modal.classList.add('hidden');
    window.deleteId = null;
  }, 300);
}

async function executeDelete() {
  if (!window.deleteId || isDeleting) return;

  isDeleting = true;
  const deleteButton = document.getElementById('deleteButton');
  const originalContent = deleteButton.innerHTML;

  deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menghapus...';
  deleteButton.disabled = true;

  try {
    const response = await fetch(`/sr/${window.deleteId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      }
    });

    const result = await response.json();

    if (result.success) {
      closeDeleteModal();
      // Trigger Alpine.js to refetch data
      Alpine.store('refreshSk', true);
      setTimeout(() => location.reload(), 500);
    } else {
      throw new Error('Delete failed');
    }
  } catch (error) {
    console.error('Delete error:', error);
    deleteButton.innerHTML = originalContent;
    deleteButton.disabled = false;
    alert('Terjadi kesalahan saat menghapus data. Silakan coba lagi.');
  } finally {
    isDeleting = false;
  }
}

// Close modal handlers
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
  if (e.target === this && !isDeleting) {
    closeDeleteModal();
  }
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape' && !isDeleting) {
    closeDeleteModal();
  }
});

// Rejection Details Hover Popup
const loadedRejections = new Set();
const hideTimers = {};
window.currentSrId = null;

function showRejectionPopup(srId, triggerElement) {
  // Clear any existing timer
  if (hideTimers[srId]) {
    clearTimeout(hideTimers[srId]);
    delete hideTimers[srId];
  }

  const popup = document.getElementById('rejection-popup-container');
  const contentEl = document.getElementById('rejection-popup-content');

  if (!popup) return;

  // Store current SR ID globally
  window.currentSrId = srId;

  // Get trigger position
  const triggerRect = triggerElement.getBoundingClientRect();
  const popupHeight = 400;
  const viewportHeight = window.innerHeight;
  const viewportWidth = window.innerWidth;

  // Calculate horizontal position (position to the right of trigger)
  let leftPos = triggerRect.right + 8; // 8px spacing from trigger
  // Ensure popup doesn't overflow right edge
  if (leftPos + 384 > viewportWidth) {
    leftPos = triggerRect.left - 384 - 8; // Show on left side if no space on right
  }

  // Calculate vertical position (align with trigger top)
  let topPos = triggerRect.top;

  // Adjust if popup would overflow bottom
  if (topPos + popupHeight > viewportHeight) {
    topPos = viewportHeight - popupHeight - 20;
  }

  // Adjust if popup would overflow top
  if (topPos < 20) {
    topPos = 20;
  }

  // Position popup
  popup.style.left = `${leftPos}px`;
  popup.style.top = `${topPos}px`;
  popup.classList.remove('hidden');

  // Load data if not loaded yet
  if (!loadedRejections.has(srId)) {
    // Reset content
    contentEl.innerHTML = '<div class="flex items-center justify-center py-4 text-xs text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Loading...</div>';
    loadRejectionPopup(srId);
  }
}

function hideRejectionPopup(srId) {
  // Set timer specific to this popup
  hideTimers[srId] = setTimeout(() => {
    const popup = document.getElementById('rejection-popup-container');
    if (popup && window.currentSrId === srId) {
      popup.classList.add('hidden');
      window.currentSrId = null;
    }
    delete hideTimers[srId];
  }, 200);
}

function keepPopupOpen(srId) {
  if (hideTimers[srId]) {
    clearTimeout(hideTimers[srId]);
    delete hideTimers[srId];
  }
}

async function loadRejectionPopup(srId) {
  const contentDiv = document.getElementById('rejection-popup-content');
  if (!contentDiv) return;

  try {
    const response = await fetch(`/sr/${srId}/rejection-details`, {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
      }
    });

    if (!response.ok) throw new Error('Failed to load rejection details');

    const data = await response.json();

    if (data.success && data.rejections && data.rejections.length > 0) {
      let html = '<div class="space-y-2">';

      data.rejections.forEach((rejection) => {
        const rejectedBy = rejection.rejected_by_type === 'tracer' ? 'Tracer' : 'CGP';
        const badgeColor = rejection.rejected_by_type === 'tracer' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700';

        html += `
          <div class="border-l-2 ${rejection.rejected_by_type === 'tracer' ? 'border-blue-400' : 'border-orange-400'} pl-2 py-2">
            <div class="flex items-start justify-between mb-1">
              <div class="font-medium text-xs text-gray-900">${rejection.slot_label}</div>
              <span class="px-1.5 py-0.5 rounded text-xs font-medium ${badgeColor}">${rejectedBy}</span>
            </div>
            <div class="text-xs text-gray-600 mb-1">${rejection.reason || 'No reason provided'}</div>
            <div class="flex items-center justify-between text-xs text-gray-500">
              <span>${rejection.rejected_date}</span>
              ${rejection.rejected_by_name ? `<span>${rejection.rejected_by_name}</span>` : ''}
            </div>
          </div>
        `;
      });

      html += '</div>';
      contentDiv.innerHTML = html;
      loadedRejections.add(srId);
    } else {
      contentDiv.innerHTML = '<div class="text-xs text-gray-500 text-center py-2">No rejections found</div>';
    }
  } catch (error) {
    console.error('Error loading rejection details:', error);
    contentDiv.innerHTML = '<div class="text-xs text-red-500 text-center py-2">Failed to load</div>';
  }
}
</script>
@endpush

@endsection
