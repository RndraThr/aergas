@extends('layouts.app')

@section('title', 'Detail SR - AERGAS')

@section('content')
@php
  $sr->loadMissing(['calonPelanggan','photoApprovals']);
  $cfgSlots = (array) (config('aergas_photos.modules.SR.slots') ?? []);
  $slotLabels = [];
  foreach ($cfgSlots as $k => $r) {
    $slotLabels[$k] = $r['label'] ?? $k;
  }
@endphp

<div class="space-y-6" x-data="srShow()" x-init="init()">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-bold text-gray-800">Detail SR</h1>
      <p class="text-gray-600 mt-1">Reff ID: <b>{{ $sr->reff_id_pelanggan }}</b></p>
    </div>
    <div class="flex gap-2">
      @if($sr->status === 'draft')
        <a href="{{ route('sr.edit',$sr->id) }}" class="px-4 py-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">Edit</a>
      @endif
      
      @if(in_array(auth()->user()->role, ['admin', 'super_admin', 'tracer']))
        <a href="{{ route('sr.berita-acara', $sr->id) }}" 
           class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors"
           target="_blank">
          <i class="fas fa-file-pdf"></i>
          Generate Berita Acara
        </a>
      @endif
      
      <a href="{{ route('sr.index') }}" class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">Kembali</a>
    </div>
  </div>

  <div class="bg-white rounded-xl card-shadow p-6">
    <div class="flex items-center gap-3 mb-4">
      <i class="fas fa-info-circle text-blue-600"></i>
      <h2 class="font-semibold text-gray-800">Informasi SR</h2>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div>
        <div class="text-xs text-gray-500">Petugas SR</div>
        @if($sr->createdBy)
          <div class="flex items-center mt-1">
            <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mr-2">
              <span class="text-xs font-medium text-blue-600">
                {{ strtoupper(substr($sr->createdBy->name, 0, 1)) }}
              </span>
            </div>
            <span class="font-medium">{{ $sr->createdBy->name }}</span>
          </div>
        @else
          <div class="font-medium text-gray-400">-</div>
        @endif
      </div>
      <div>
        <div class="text-xs text-gray-500">Tanggal Pemasangan</div>
        <div class="font-medium">{{ $sr->tanggal_pemasangan ? $sr->tanggal_pemasangan->format('d/m/Y') : '-' }}</div>
      </div>
      <div>
        <div class="text-xs text-gray-500">Status</div>
        <span class="px-2 py-0.5 rounded text-xs
          @class([
            'bg-gray-100 text-gray-700' => $sr->status === 'draft',
            'bg-blue-100 text-blue-800' => $sr->status === 'ready_for_tracer',
            'bg-yellow-100 text-yellow-800' => $sr->status === 'scheduled',
            'bg-purple-100 text-purple-800' => $sr->status === 'tracer_approved',
            'bg-amber-100 text-amber-800' => $sr->status === 'cgp_approved',
            'bg-red-100 text-red-800' => str_contains($sr->status,'rejected'),
            'bg-green-100 text-green-800' => $sr->status === 'completed',
          ])
        ">{{ strtoupper($sr->status) }}</span>
      </div>
      <div>
        <div class="text-xs text-gray-500">Created At</div>
        <div class="font-medium">{{ $sr->created_at ? $sr->created_at->format('d/m/Y H:i') : '-' }}</div>
      </div>
    </div>

    @if($sr->no_seri_mgrt || $sr->merk_brand_mgrt || $sr->notes)
      <div class="mt-6 pt-4 border-t border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          @if($sr->no_seri_mgrt)
            <div>
              <div class="text-xs text-gray-500">No. Seri MGRT</div>
              <div class="font-medium text-blue-600">{{ $sr->no_seri_mgrt }}</div>
            </div>
          @endif
          
          @if($sr->merk_brand_mgrt)
            <div>
              <div class="text-xs text-gray-500">Merk/Brand MGRT</div>
              <div class="font-medium">{{ $sr->merk_brand_mgrt }}</div>
            </div>
          @endif

          @if($sr->notes)
            <div class="md:col-span-{{ $sr->no_seri_mgrt && $sr->merk_brand_mgrt ? '1' : ($sr->no_seri_mgrt || $sr->merk_brand_mgrt ? '2' : '3') }}">
              <div class="text-xs text-gray-500">Catatan Petugas</div>
              <div class="font-medium text-gray-700">{{ $sr->notes }}</div>
            </div>
          @endif
        </div>
      </div>
    @endif
  </div>

  @if($sr->tracer_approved_at || $sr->cgp_approved_at)
    <div class="bg-white rounded-xl card-shadow p-6">
      <div class="flex items-center gap-3 mb-4">
        <i class="fas fa-clipboard-check text-green-600"></i>
        <h2 class="font-semibold text-gray-800">Timeline Approval</h2>
      </div>

      <div class="space-y-4">
        @if($sr->tracer_approved_at)
          <div class="flex items-start space-x-4 p-4 bg-purple-50 rounded-lg border border-purple-200">
            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
              <i class="fas fa-search text-purple-600"></i>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="font-medium text-purple-800">Tracer Approval</h4>
                  <p class="text-sm text-purple-600">{{ $sr->tracer_approved_at->format('d/m/Y H:i') }}</p>
                </div>
                @if($sr->tracerApprovedBy)
                  <div class="text-right">
                    <div class="text-sm font-medium text-purple-700">{{ $sr->tracerApprovedBy->name }}</div>
                    <div class="text-xs text-purple-600">{{ ucfirst($sr->tracerApprovedBy->role) }}</div>
                  </div>
                @endif
              </div>
              @if($sr->tracer_notes)
                <div class="mt-2 p-2 bg-white rounded border">
                  <div class="text-xs text-gray-500 mb-1">Notes:</div>
                  <div class="text-sm text-gray-700">{{ $sr->tracer_notes }}</div>
                </div>
              @endif
            </div>
          </div>
        @endif

        @if($sr->cgp_approved_at)
          <div class="flex items-start space-x-4 p-4 bg-green-50 rounded-lg border border-green-200">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
              <i class="fas fa-check-circle text-green-600"></i>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="font-medium text-green-800">CGP Approval</h4>
                  <p class="text-sm text-green-600">{{ $sr->cgp_approved_at->format('d/m/Y H:i') }}</p>
                </div>
                @if($sr->cgpApprovedBy)
                  <div class="text-right">
                    <div class="text-sm font-medium text-green-700">{{ $sr->cgpApprovedBy->name }}</div>
                    <div class="text-xs text-green-600">{{ ucfirst($sr->cgpApprovedBy->role) }}</div>
                  </div>
                @endif
              </div>
              @if($sr->cgp_notes)
                <div class="mt-2 p-2 bg-white rounded border">
                  <div class="text-xs text-gray-500 mb-1">Notes:</div>
                  <div class="text-sm text-gray-700">{{ $sr->cgp_notes }}</div>
                </div>
              @endif
            </div>
          </div>
        @endif
      </div>
    </div>
  @endif

  @if($sr->calonPelanggan)
    <div class="bg-white rounded-xl card-shadow p-6">
      <div class="flex items-center gap-3 mb-4">
        <i class="fas fa-user text-blue-600"></i>
        <h2 class="font-semibold text-gray-800">Informasi Customer</h2>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <div class="text-xs text-gray-500">Reference ID</div>
          <div class="font-medium text-blue-600">{{ $sr->reff_id_pelanggan }}</div>
        </div>
        <div>
          <div class="text-xs text-gray-500">Nama Pelanggan</div>
          <div class="font-medium">{{ $sr->calonPelanggan->nama_pelanggan }}</div>
        </div>
        <div>
          <div class="text-xs text-gray-500">No. Telepon</div>
          <div class="font-medium">{{ $sr->calonPelanggan->no_telepon ?? '-' }}</div>
        </div>
        <div>
          <div class="text-xs text-gray-500">Status Customer</div>
          <span class="px-2 py-0.5 rounded text-xs
            @class([
              'bg-gray-100 text-gray-700' => $sr->calonPelanggan->status === 'pending',
              'bg-green-100 text-green-800' => $sr->calonPelanggan->status === 'lanjut',
              'bg-blue-100 text-blue-800' => $sr->calonPelanggan->status === 'in_progress',
              'bg-red-100 text-red-800' => $sr->calonPelanggan->status === 'batal',
            ])
          ">{{ strtoupper($sr->calonPelanggan->status ?? '-') }}</span>
        </div>
        <div class="md:col-span-2 lg:col-span-3">
          <div class="text-xs text-gray-500">Alamat</div>
          <div class="font-medium">{{ $sr->calonPelanggan->alamat ?? '-' }}</div>
        </div>
        <div>
          <div class="text-xs text-gray-500">Kelurahan</div>
          <div class="font-medium">{{ $sr->calonPelanggan->kelurahan ?? '-' }}</div>
        </div>
        @if($sr->calonPelanggan->padukuhan)
          <div class="md:col-span-1">
            <div class="text-xs text-gray-500">Padukuhan</div>
            <div class="font-medium">{{ $sr->calonPelanggan->padukuhan }}</div>
          </div>
        @endif
      </div>
    </div>
  @endif

  <div class="bg-white rounded-xl card-shadow p-6">
    <div class="flex items-center gap-3 mb-4">
      <i class="fas fa-clipboard-list text-orange-600"></i>
      <h2 class="font-semibold text-gray-800">Material SR</h2>
    </div>

    @php
      $materialLabels = $sr->getMaterialLabels();
      $materialData = [];
      $totalItems = 0;
      $totalLengths = 0;

      foreach($sr->getRequiredMaterialItems() as $field => $value) {
        if($value > 0) {
          $materialData[] = ['label' => $materialLabels[$field] ?? $field, 'value' => $value, 'field' => $field];
          if(str_contains($field, 'panjang_')) {
            $totalLengths += $value;
          } else {
            $totalItems += $value;
          }
        }
      }

      foreach($sr->getOptionalMaterialItems() as $field => $value) {
        if($value && $value > 0) {
          $materialData[] = ['label' => $materialLabels[$field] ?? $field, 'value' => $value, 'field' => $field];
        }
      }
    @endphp

    @if(empty($materialData))
      <div class="text-center py-6">
        <i class="fas fa-box-open text-gray-300 text-3xl mb-3"></i>
        <p class="text-gray-500 text-sm">Belum ada data material</p>
      </div>
    @else
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($materialData as $material)
          <div class="bg-gray-50 p-4 rounded-lg border">
            <div class="text-xs text-gray-500 mb-1">{{ $material['label'] }}</div>
            <div class="font-bold text-lg text-gray-800">
              {{ $material['value'] }}
              @if(str_contains($material['field'], 'panjang_'))
                <span class="text-sm font-normal text-gray-600">meter</span>
              @else
                <span class="text-sm font-normal text-gray-600">pcs</span>
              @endif
            </div>
          </div>
        @endforeach
      </div>

      <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <div class="flex justify-between items-center">
          <span class="font-medium text-blue-800">Total Items:</span>
          <span class="font-bold text-blue-900 text-lg">{{ $totalItems }} pcs</span>
        </div>
        <div class="flex justify-between items-center mt-2">
          <span class="font-medium text-blue-800">Total Lengths:</span>
          <span class="font-bold text-blue-900 text-lg">{{ $totalLengths }} meter</span>
        </div>
        @if($sr->jenis_tapping)
          <div class="flex justify-between items-center mt-2">
            <span class="font-medium text-blue-800">Jenis Tapping:</span>
            <span class="font-bold text-blue-900 text-lg">{{ $sr->jenis_tapping }}</span>
          </div>
        @endif
      </div>
    @endif
  </div>

  <div class="bg-white rounded-xl card-shadow p-6 space-y-4">
    <div class="flex items-center gap-3">
      <i class="fas fa-images text-purple-600"></i>
      <h2 class="font-semibold text-gray-800">Dokumentasi Foto</h2>
    </div>

    @php
      $list = $sr->photoApprovals->sortBy('photo_field_name')->values();
    @endphp

    @if($list->isEmpty())
      <div class="text-center py-8">
        <i class="fas fa-camera text-gray-300 text-4xl mb-3"></i>
        <p class="text-gray-500 text-sm mb-4">Belum ada foto yang diupload</p>
        @if($sr->status === 'draft')
          <a href="{{ route('sr.edit',$sr->id) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Upload Foto
          </a>
        @endif
      </div>
    @else
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($list as $pa)
          <div class="border rounded-lg p-4 space-y-3">
            <div class="flex items-start justify-between">
              <div>
                <div class="text-xs text-gray-500">Slot</div>
                <div class="font-medium">{{ $slotLabels[$pa->photo_field_name] ?? $pa->photo_field_name }}</div>
              </div>
              <div class="text-xs text-gray-500">
                {{ $pa->created_at ? $pa->created_at->format('d/m H:i') : '-' }}
              </div>
            </div>

            @if($pa->photo_url)
              @php
                $originalUrl = $pa->photo_url;
                $photoUrl = $originalUrl;
                $isPdf = str_ends_with(Str::lower($originalUrl), '.pdf');

                if (strpos($originalUrl, 'drive.google.com') !== false && preg_match('/\/file\/d\/([a-zA-Z0-9-_]+)/', $originalUrl, $matches)) {
                  $fileId = $matches[1];
                  $photoUrl = "https://lh3.googleusercontent.com/d/{$fileId}=w800";
                }
              @endphp

              @if(!$isPdf)
                <div class="relative group">
                  <img src="{{ $photoUrl }}"
                       class="w-full h-48 object-cover rounded border cursor-pointer hover:opacity-90 transition-opacity"
                       alt="Photo {{ $pa->photo_field_name }}"
                       loading="lazy"
                       onerror="this.onerror=null; this.src='{{ $originalUrl }}'; if(this.onerror) this.style.display='none'; this.nextElementSibling.style.display='block';"
                       onclick="openImageModal('{{ $photoUrl }}', '{{ $slotLabels[$pa->photo_field_name] ?? $pa->photo_field_name }}')">

                  <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 rounded flex items-center justify-center opacity-0 group-hover:opacity-100">
                    <i class="fas fa-search-plus text-white text-xl"></i>
                  </div>

                  <div class="w-full h-48 bg-gray-100 rounded border-2 border-dashed border-gray-300 flex-col items-center justify-center text-gray-500 hidden">
                    <i class="fas fa-image text-3xl mb-2"></i>
                    <p class="text-xs text-center mb-2">Foto tidak dapat dimuat</p>
                    <a href="{{ $originalUrl }}" target="_blank" class="text-xs text-blue-600 hover:underline">
                      Buka di tab baru
                    </a>
                  </div>
                </div>
              @else
                <div class="w-full h-48 flex flex-col items-center justify-center bg-gray-50 rounded border hover:bg-gray-100 transition-colors cursor-pointer"
                     onclick="window.open('{{ $originalUrl }}', '_blank')">
                  <i class="fas fa-file-pdf text-red-500 text-4xl mb-3"></i>
                  <div class="text-sm text-gray-600 text-center font-medium">PDF Document</div>
                  <div class="text-xs text-blue-600 mt-1">Klik untuk membuka</div>
                </div>
              @endif
            @else
              <div class="w-full h-48 flex items-center justify-center bg-gray-50 rounded border">
                <div class="text-center text-gray-400">
                  <i class="fas fa-image text-3xl mb-2"></i>
                  <div class="text-xs">Foto tidak tersedia</div>
                </div>
              </div>
            @endif

            <div class="text-xs text-gray-500 text-center">
              {{ $pa->photo_field_name }}
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>
</div>

<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center p-4" onclick="closeImageModal()">
  <div class="relative max-w-4xl max-h-full">
    <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white text-2xl hover:text-gray-300 z-10">
      <i class="fas fa-times"></i>
    </button>
    <img id="modalImage" src="" alt="" class="max-w-full max-h-full object-contain rounded">
    <div id="modalTitle" class="absolute bottom-4 left-4 text-white bg-black bg-opacity-50 px-3 py-1 rounded"></div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function srShow() {
  return {
    init() {}
  }
}

function openImageModal(imageUrl, title) {
  const modal = document.getElementById('imageModal');
  const modalImage = document.getElementById('modalImage');
  const modalTitle = document.getElementById('modalTitle');

  modalImage.src = imageUrl;
  modalTitle.textContent = title;
  modal.classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}

function closeImageModal() {
  const modal = document.getElementById('imageModal');
  modal.classList.add('hidden');
  document.body.style.overflow = 'auto';
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeImageModal();
  }
});
</script>
@endpush
