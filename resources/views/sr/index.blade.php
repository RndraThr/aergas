@extends('layouts.app')

@section('title', 'Data SR - AERGAS')

@section('content')
<div class="space-y-6">

  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-bold text-gray-800">Data SR</h1>
      <p class="text-gray-600 mt-1">Daftar Sambungan Rumah</p>
    </div>
    <div class="flex gap-2">
      <a href="{{ route('sr.index') }}" class="px-3 py-2 bg-gray-100 rounded hover:bg-gray-200">
        <i class="fas fa-sync-alt mr-1"></i>Refresh
      </a>
      <a href="{{ route('sr.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
        <i class="fas fa-plus mr-2"></i>Buat SR
      </a>
    </div>
  </div>

  <form method="get" action="{{ route('sr.index') }}" class="bg-white p-4 rounded-xl card-shadow">
    <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
      <div class="md:col-span-4">
        <input type="text" name="q" value="{{ request('q') }}"
               placeholder="Cari Reff ID, Customer, atau Status..."
               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <select name="status" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
          <option value="">Semua Status</option>
          <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
          <option value="ready_for_tracer" {{ request('status') == 'ready_for_tracer' ? 'selected' : '' }}>Ready for Tracer</option>
          <option value="tracer_approved" {{ request('status') == 'tracer_approved' ? 'selected' : '' }}>Tracer Approved</option>
          <option value="cgp_approved" {{ request('status') == 'cgp_approved' ? 'selected' : '' }}>CGP Approved</option>
          <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
          <option value="rejected" {{ str_contains(request('status'), 'rejected') ? 'selected' : '' }}>Rejected</option>
        </select>
      </div>
      <div>
        <button class="w-full px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">
          <i class="fas fa-search mr-1"></i>Cari
        </button>
      </div>
    </div>
  </form>

  @php
    $totalCount = $sr->total();
    $draftCount = $sr->where('status', 'draft')->count();
    $readyCount = $sr->where('status', 'ready_for_tracer')->count();
    $completedCount = $sr->where('status', 'completed')->count();
  @endphp

  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white p-4 rounded-lg card-shadow">
      <div class="text-2xl font-bold text-blue-600">{{ $totalCount }}</div>
      <div class="text-sm text-gray-600">Total SR</div>
    </div>
    <div class="bg-white p-4 rounded-lg card-shadow">
      <div class="text-2xl font-bold text-yellow-600">{{ $draftCount }}</div>
      <div class="text-sm text-gray-600">Draft</div>
    </div>
    <div class="bg-white p-4 rounded-lg card-shadow">
      <div class="text-2xl font-bold text-purple-600">{{ $readyCount }}</div>
      <div class="text-sm text-gray-600">Ready for Review</div>
    </div>
    <div class="bg-white p-4 rounded-lg card-shadow">
      <div class="text-2xl font-bold text-green-600">{{ $completedCount }}</div>
      <div class="text-sm text-gray-600">Completed</div>
    </div>
  </div>

  <div class="bg-white rounded-xl card-shadow overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reff ID</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
          <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        @forelse ($sr as $row)
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-700">{{ $row->id }}</td>
            <td class="px-4 py-3 text-sm font-medium text-blue-600">
              <a href="{{ route('sr.show', $row->id) }}" class="hover:text-blue-800">
                {{ $row->reff_id_pelanggan }}
              </a>
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">
              {{ $row->calonPelanggan->nama_pelanggan ?? '-' }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">
              @if($row->createdBy)
                <div class="flex items-center">
                  <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mr-2">
                    <span class="text-xs font-medium text-blue-600">
                      {{ strtoupper(substr($row->createdBy->name, 0, 1)) }}
                    </span>
                  </div>
                  <span class="text-sm">{{ $row->createdBy->name }}</span>
                </div>
              @else
                <span class="text-gray-400 text-sm">-</span>
              @endif
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">
              {{ $row->tanggal_pemasangan ? $row->tanggal_pemasangan->format('d/m/Y') : '-' }}
            </td>
            <td class="px-4 py-3">
              <span class="px-2 py-1 text-xs font-medium rounded-full
                @class([
                  'bg-gray-100 text-gray-700' => $row->status === 'draft',
                  'bg-blue-100 text-blue-800' => $row->status === 'ready_for_tracer',
                  'bg-yellow-100 text-yellow-800' => $row->status === 'scheduled',
                  'bg-purple-100 text-purple-800' => $row->status === 'tracer_approved',
                  'bg-amber-100 text-amber-800' => $row->status === 'cgp_approved',
                  'bg-red-100 text-red-800' => str_contains($row->status,'rejected'),
                  'bg-green-100 text-green-800' => $row->status === 'completed',
                ])
              ">{{ ucwords(str_replace('_', ' ', $row->status)) }}</span>
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex justify-end gap-1">
                <a href="{{ route('sr.show',$row->id) }}"
                   class="px-3 py-1.5 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200"
                   title="Lihat Detail">
                  <i class="fas fa-eye mr-1"></i>Detail
                </a>
                @if($row->status === 'draft')
                  <a href="{{ route('sr.edit',$row->id) }}"
                     class="px-3 py-1.5 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200"
                     title="Edit SR">
                    <i class="fas fa-edit mr-1"></i>Edit
                  </a>
                  <button onclick="confirmDelete({{ $row->id }}, '{{ $row->reff_id_pelanggan }}')"
                          class="px-3 py-1.5 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200"
                          title="Hapus SR">
                    <i class="fas fa-trash mr-1"></i>Hapus
                  </button>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
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
        @endforelse
      </tbody>
    </table>
  </div>

  @if($sr->hasPages())
    <div class="bg-white rounded-lg card-shadow p-4">
      {{ $sr->links() }}
    </div>
  @endif
</div>

{{-- Delete Confirmation Modal --}}
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

{{-- Success Toast --}}
<div id="successToast" class="fixed top-4 right-4 bg-green-600 text-white px-6 py-4 rounded-lg shadow-2xl transform translate-x-full transition-transform duration-300 z-50">
  <div class="flex items-center">
    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center mr-3">
      <i class="fas fa-check text-white"></i>
    </div>
    <div>
      <p class="font-semibold">Data Berhasil Dihapus</p>
      <p class="text-sm text-green-100" id="deleteDetails">SR telah dihapus dari sistem</p>
    </div>
  </div>
</div>

<script>
let deleteId = null;
let isDeleting = false;

function confirmDelete(id, reffId) {
  deleteId = id;
  document.getElementById('deleteReffId').textContent = reffId;
  showDeleteModal();
}

function showDeleteModal() {
  const modal = document.getElementById('deleteModal');
  const modalContent = document.getElementById('deleteModalContent');

  modal.classList.remove('hidden');

  // Trigger animation after DOM update
  setTimeout(() => {
    modal.classList.remove('bg-opacity-50');
    modal.classList.add('bg-opacity-50');
    modalContent.classList.remove('scale-95', 'opacity-0');
    modalContent.classList.add('scale-100', 'opacity-100');
  }, 10);
}

function closeDeleteModal() {
  if (isDeleting) return; // Prevent closing during delete operation

  const modal = document.getElementById('deleteModal');
  const modalContent = document.getElementById('deleteModalContent');

  modalContent.classList.remove('scale-100', 'opacity-100');
  modalContent.classList.add('scale-95', 'opacity-0');

  setTimeout(() => {
    modal.classList.add('hidden');
    deleteId = null;
  }, 300);
}

async function executeDelete() {
  if (!deleteId || isDeleting) return;

  isDeleting = true;
  const deleteButton = document.getElementById('deleteButton');
  const originalContent = deleteButton.innerHTML;

  // Show loading state
  deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menghapus...';
  deleteButton.disabled = true;
  deleteButton.classList.add('cursor-not-allowed', 'opacity-75');

  try {
    // Use fetch for better control
    const response = await fetch(`/sr/${deleteId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      }
    });

    const result = await response.json();

    if (result.success) {
      // Close modal with animation
      closeDeleteModal();

      // Show success toast with file deletion info
      showSuccessToast(result);

      // Wait for modal to close, then refresh page
      setTimeout(() => {
        window.location.reload();
      }, 1200);
    } else {
      throw new Error('Delete failed');
    }
  } catch (error) {
    console.error('Delete error:', error);

    // Reset button state
    deleteButton.innerHTML = originalContent;
    deleteButton.disabled = false;
    deleteButton.classList.remove('cursor-not-allowed', 'opacity-75');

    // Show error (you can enhance this with an error toast)
    alert('Terjadi kesalahan saat menghapus data. Silakan coba lagi.');
  } finally {
    isDeleting = false;
  }
}

function showSuccessToast(result = {}) {
  const toast = document.getElementById('successToast');
  const details = document.getElementById('deleteDetails');

  // Update toast message with folder deletion info
  let message = 'SR telah dihapus dari sistem';
  if (result.folders_deleted > 0) {
    message += ` termasuk ${result.folders_deleted} folder di Google Drive`;
  }
  details.textContent = message;

  toast.classList.remove('translate-x-full');
  toast.classList.add('translate-x-0');

  // Auto hide after 4 seconds (longer to read the message)
  setTimeout(() => {
    toast.classList.remove('translate-x-0');
    toast.classList.add('translate-x-full');
  }, 4000);
}

// Close modal when clicking outside (only if not deleting)
document.getElementById('deleteModal').addEventListener('click', function(e) {
  if (e.target === this && !isDeleting) {
    closeDeleteModal();
  }
});

// Close modal with ESC key (only if not deleting)
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape' && !isDeleting) {
    closeDeleteModal();
  }
});

// Pagination state persistence
document.addEventListener('DOMContentLoaded', function() {
  const storageKey = 'sr_pagination_state';

  // Save current page state when navigating to detail page
  document.querySelectorAll('a[href*="/sr/"]').forEach(link => {
    if (link.href.includes('/sr/') && !link.href.includes('/create') && !link.href.includes('/edit')) {
      link.addEventListener('click', function() {
        const currentUrl = new URL(window.location.href);
        const currentPage = currentUrl.searchParams.get('page') || '1';
        const searchParams = currentUrl.search;

        localStorage.setItem(storageKey, JSON.stringify({
          page: currentPage,
          search: searchParams,
          timestamp: Date.now()
        }));
      });
    }
  });

  // Handle back button and restore state
  window.addEventListener('pageshow', function(event) {
    if (event.persisted || performance.navigation.type === 2) {
      // Page came from cache (back button)
      const savedState = localStorage.getItem(storageKey);
      if (savedState) {
        try {
          const state = JSON.parse(savedState);
          // Check if state is recent (within 10 minutes)
          if (Date.now() - state.timestamp < 600000) {
            const currentUrl = new URL(window.location.href);
            const currentPage = currentUrl.searchParams.get('page') || '1';

            // Only redirect if we're on page 1 and saved state has different page
            if (currentPage === '1' && state.page !== '1') {
              currentUrl.searchParams.set('page', state.page);
              // Restore other search parameters if any
              if (state.search) {
                const savedParams = new URLSearchParams(state.search);
                for (const [key, value] of savedParams) {
                  if (key !== 'page') {
                    currentUrl.searchParams.set(key, value);
                  }
                }
              }
              window.location.href = currentUrl.href;
            }
          }
        } catch (e) {
          console.log('Error parsing pagination state:', e);
        }
      }
    }
  });

  // Clean up old states periodically
  const cleanupOldStates = () => {
    const savedState = localStorage.getItem(storageKey);
    if (savedState) {
      try {
        const state = JSON.parse(savedState);
        if (Date.now() - state.timestamp > 600000) { // 10 minutes
          localStorage.removeItem(storageKey);
        }
      } catch (e) {
        localStorage.removeItem(storageKey);
      }
    }
  };

  cleanupOldStates();
});
</script>

@endsection
