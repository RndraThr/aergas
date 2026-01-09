<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\JalurLineNumber;
use App\Models\JalurLoweringData;
use App\Models\JalurCluster;
use App\Models\PhotoApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\FileUploadService;
use App\Services\GoogleDriveService;
use App\Services\PhotoApprovalService;

class JalurLoweringController extends Controller
{

    public function index(Request $request)
    {
        $query = JalurLoweringData::query()->with(['lineNumber.cluster', 'tracerApprover', 'cgpApprover']);

        if ($request->filled('search')) {
            $query->whereHas('lineNumber', function ($q) use ($request) {
                $q->where('line_number', 'like', "%{$request->search}%");
            })->orWhere('nama_jalan', 'like', "%{$request->search}%");
        }

        if ($request->filled('cluster_id')) {
            $query->whereHas('lineNumber', function ($q) use ($request) {
                $q->where('cluster_id', $request->cluster_id);
            });
        }

        if ($request->filled('line_number_id')) {
            $query->byLineNumber($request->line_number_id);
        }

        if ($request->filled('status')) {
            $query->where('status_laporan', $request->status);
        }

        if ($request->filled('tipe_bongkaran')) {
            $query->byTipeBongkaran($request->tipe_bongkaran);
        }

        if ($request->filled('tipe_material')) {
            $query->where('tipe_material', $request->tipe_material);
        }

        $loweringData = $query->latest()->paginate(15);
        $clusters = JalurCluster::active()->get();
        $lineNumbers = JalurLineNumber::active()->with('cluster')->get();

        return view('jalur.lowering.index', compact('loweringData', 'clusters', 'lineNumbers'));
    }

    public function create(Request $request)
    {
        $clusters = JalurCluster::active()->get();
        $lineNumbers = collect();

        if ($request->filled('cluster_id')) {
            $lineNumbers = JalurLineNumber::byCluster($request->cluster_id)->active()->get();
        }

        return view('jalur.lowering.create', compact('clusters', 'lineNumbers'));
    }

    public function store(Request $request)
    {
        // Increase execution time for Google Drive uploads (multiple photos can take time)
        set_time_limit(120); // 2 minutes

        // DEBUG: Log all incoming request data
        Log::info('=== LOWERING STORE REQUEST DEBUG ===');
        Log::info('Tipe Bongkaran: ' . $request->input('tipe_bongkaran'));
        Log::info('aksesoris_cassing: ' . ($request->has('aksesoris_cassing') ? 'YES - Value: ' . $request->input('aksesoris_cassing') : 'NO'));
        Log::info('cassing_quantity: ' . ($request->has('cassing_quantity') ? $request->input('cassing_quantity') : 'NOT SENT'));
        Log::info('cassing_type: ' . ($request->has('cassing_type') ? $request->input('cassing_type') : 'NOT SENT'));
        Log::info('All request data: ' . json_encode($request->all()));

        // Determine upload method
        $uploadMethod = $request->input('upload_method', 'file');

        $validationRules = [
            'diameter' => 'required|in:63,90,180',
            'cluster_id' => 'required|exists:jalur_clusters,id',
            'line_number_suffix' => 'required|string|max:10|regex:/^[0-9A-Za-z]+$/',
            'tanggal_jalur' => 'required|date',
            'tipe_bongkaran' => 'required|in:Manual Boring,Open Cut,Crossing,Zinker,HDD,Manual Boring - PK,Crossing - PK',
            'tipe_material' => 'nullable|in:Aspal,Tanah,Paving,Beton',
            'penggelaran' => 'required|numeric|min:0.01',
            'bongkaran' => 'required|numeric|min:0.01',
            'kedalaman_lowering' => 'required|numeric|min:1',
            'aksesoris_cassing' => 'boolean',
            'aksesoris_marker_tape' => 'boolean',
            'aksesoris_concrete_slab' => 'boolean',
            'marker_tape_quantity' => 'required_if:aksesoris_marker_tape,1|nullable|numeric|min:0.1',
            'concrete_slab_quantity' => 'required_if:aksesoris_concrete_slab,1|nullable|integer|min:1',
            'cassing_quantity' => 'required_if:aksesoris_cassing,1|nullable|numeric|min:0.1',
            'cassing_type' => 'required_if:aksesoris_cassing,1|nullable|in:4_inch,8_inch',
            'keterangan' => 'nullable|string|max:1000',
        ];

        // Add conditional validation for main photo upload method
        if ($uploadMethod === 'file') {
            $validationRules['foto_evidence_penggelaran_bongkaran'] = 'required|image|mimes:jpeg,jpg,png|max:35840';
        } else {
            $validationRules['foto_evidence_penggelaran_bongkaran_link'] = 'required|url';
        }

        // Add conditional validation for cassing photo (if cassing checkbox is checked)
        if ($request->has('aksesoris_cassing')) {
            $uploadMethodCassing = $request->input('upload_method_cassing', 'file');

            if ($uploadMethodCassing === 'file') {
                $validationRules['foto_evidence_cassing'] = 'required|image|mimes:jpeg,jpg,png|max:35840';
            } else {
                $validationRules['foto_evidence_cassing_link'] = 'required|url';
            }
        }

        // Add conditional validation for marker tape photo
        if ($request->has('aksesoris_marker_tape')) {
            $uploadMethodMarkerTape = $request->input('upload_method_marker_tape', 'file');

            if ($uploadMethodMarkerTape === 'file') {
                $validationRules['foto_evidence_marker_tape'] = 'required|image|mimes:jpeg,jpg,png|max:35840';
            } else {
                $validationRules['foto_evidence_marker_tape_link'] = 'required|url';
            }
        }

        // Add conditional validation for concrete slab photo
        if ($request->has('aksesoris_concrete_slab')) {
            $uploadMethodConcreteSlab = $request->input('upload_method_concrete_slab', 'file');

            if ($uploadMethodConcreteSlab === 'file') {
                $validationRules['foto_evidence_concrete_slab'] = 'required|image|mimes:jpeg,jpg,png|max:35840';
            } else {
                $validationRules['foto_evidence_concrete_slab_link'] = 'required|url';
            }
        }

        $validated = $request->validate($validationRules);

        // Build complete line number from diameter, cluster code, and suffix
        $cluster = JalurCluster::findOrFail($validated['cluster_id']);
        $completeLineNumber = $validated['diameter'] . '-' . $cluster->code_cluster . '-LN' . $validated['line_number_suffix'];

        // Check if line number already exists across ALL clusters (should be globally unique)
        $existingLineNumber = JalurLineNumber::where('line_number', $completeLineNumber)->first();

        if ($existingLineNumber) {
            // Line number exists
            // Check if it belongs to different cluster
            if ($existingLineNumber->cluster_id != $validated['cluster_id']) {
                return back()
                    ->withInput()
                    ->with('error', "Line Number {$completeLineNumber} sudah digunakan di cluster {$existingLineNumber->cluster->nama_cluster}. Gunakan nomor lain.");
            }

            // Line number exists in same cluster - reuse it
            $lineNumber = $existingLineNumber;
        } else {
            // Create new line number
            // Extract line_code from suffix (LN + suffix)
            $lineCode = 'LN' . $validated['line_number_suffix'];

            $lineNumber = JalurLineNumber::create([
                'line_number' => $completeLineNumber,
                'line_code' => $lineCode,
                'cluster_id' => $validated['cluster_id'],
                'diameter' => $validated['diameter'],
                'nama_jalan' => null, // Will be filled later from line number edit
                'estimasi_panjang' => 0,
                'status_line' => 'draft',
                'is_active' => true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        }

        $validated['line_number_id'] = $lineNumber->id;

        // Get nama_jalan from line number (not from form anymore)
        // If line number doesn't have nama_jalan yet, use null or empty string
        $validated['nama_jalan'] = $lineNumber->nama_jalan ?? null;

        // Clean up fields that are not in lowering_data table
        unset($validated['diameter']);
        unset($validated['line_number_suffix']);
        unset($validated['cluster_id']); // cluster_id is not in jalur_lowering_data table

        // Auto-fill bongkaran dengan nilai penggelaran
        $validated['bongkaran'] = $validated['penggelaran'];

        // Remove file/link fields from validated data (handled separately)
        unset($validated['foto_evidence_penggelaran_bongkaran']);
        unset($validated['foto_evidence_penggelaran_bongkaran_link']);

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();
        $validated['status_laporan'] = 'draft';

        try {
            DB::beginTransaction();

            // Create lowering data record
            $lowering = JalurLoweringData::create($validated);

            // Handle main photo upload (file or Google Drive link)
            if ($uploadMethod === 'file') {
                // Handle photo uploads - multiple photos based on accessories
                $photoFields = ['foto_evidence_penggelaran_bongkaran']; // Always required

                // Add accessory photos based on what checkboxes are checked (flexible for all tipe bongkaran)
                if ($request->has('aksesoris_marker_tape') && $request->hasFile('foto_evidence_marker_tape')) {
                    $photoFields[] = 'foto_evidence_marker_tape';
                }
                if ($request->has('aksesoris_concrete_slab') && $request->hasFile('foto_evidence_concrete_slab')) {
                    $photoFields[] = 'foto_evidence_concrete_slab';
                }
                if ($request->has('aksesoris_cassing') && $request->hasFile('foto_evidence_cassing')) {
                    $photoFields[] = 'foto_evidence_cassing';
                }

                $this->handlePhotoUploads($request, $lowering, $photoFields);
            } else {
                // Handle Google Drive link for main photo
                $driveLink = $request->input('foto_evidence_penggelaran_bongkaran_link');

                try {
                    $googleDriveService = app(GoogleDriveService::class);

                    // Use same path structure as direct upload
                    $lineNumber = $lowering->lineNumber->line_number;
                    $clusterName = $lowering->lineNumber->cluster->nama_cluster;
                    $clusterSlug = \Illuminate\Support\Str::slug($clusterName, '_');
                    $tanggalFolder = \Carbon\Carbon::parse($lowering->tanggal_jalur)->format('Y-m-d');
                    $customDrivePath = "jalur_lowering/{$clusterSlug}/{$lineNumber}/{$tanggalFolder}";

                    // Generate descriptive filename
                    $waktu = date('H-i-s');
                    $fieldSlug = 'penggelaran-bongkaran';
                    $customFileName = "LOWERING_{$lineNumber}_{$tanggalFolder}_{$waktu}_{$fieldSlug}";

                    $result = $googleDriveService->copyFromDriveLink(
                        $driveLink,
                        $customDrivePath,
                        $customFileName
                    );

                    // Create photo approval record directly for jalur lowering
                    PhotoApproval::create([
                        'reff_id_pelanggan' => null, // Jalur doesn't have pelanggan
                        'module_name' => 'jalur_lowering',
                        'module_record_id' => $lowering->id,
                        'photo_field_name' => 'foto_evidence_penggelaran_bongkaran',
                        'photo_url' => $result['url'],
                        'drive_link' => $result['url'],
                        'photo_status' => 'tracer_pending', // Skip AI, go directly to tracer
                        'uploaded_by' => Auth::id(),
                        'uploaded_at' => now(),
                    ]);

                    Log::info('Google Drive photo copied successfully', [
                        'lowering_id' => $lowering->id,
                        'drive_link' => $driveLink,
                        'result' => $result
                    ]);

                } catch (\Exception $e) {
                    Log::error('Failed to copy photo from Google Drive link', [
                        'lowering_id' => $lowering->id,
                        'drive_link' => $driveLink,
                        'error' => $e->getMessage()
                    ]);
                    throw new \Exception('Gagal mengunduh foto dari Google Drive: ' . $e->getMessage());
                }
            }

            // Handle cassing photo from Google Drive link (if provided)
            if ($request->filled('foto_evidence_cassing_link') && $request->has('aksesoris_cassing')) {
                $cassingDriveLink = $request->input('foto_evidence_cassing_link');

                try {
                    $googleDriveService = app(GoogleDriveService::class);

                    $lineNumber = $lowering->lineNumber->line_number;
                    $clusterName = $lowering->lineNumber->cluster->nama_cluster;
                    $clusterSlug = \Illuminate\Support\Str::slug($clusterName, '_');
                    $tanggalFolder = \Carbon\Carbon::parse($lowering->tanggal_jalur)->format('Y-m-d');
                    $customDrivePath = "jalur_lowering/{$clusterSlug}/{$lineNumber}/{$tanggalFolder}";

                    // Generate descriptive filename  
                    $waktu = date('H-i-s');
                    $fieldSlug = 'cassing';
                    $customFileName = "LOWERING_{$lineNumber}_{$tanggalFolder}_{$waktu}_{$fieldSlug}";

                    $result = $googleDriveService->copyFromDriveLink(
                        $cassingDriveLink,
                        $customDrivePath,
                        $customFileName
                    );

                    PhotoApproval::create([
                        'reff_id_pelanggan' => null,
                        'module_name' => 'jalur_lowering',
                        'module_record_id' => $lowering->id,
                        'photo_field_name' => 'foto_evidence_cassing',
                        'photo_url' => $result['url'],
                        'drive_link' => $result['url'],
                        'photo_status' => 'tracer_pending',
                        'uploaded_by' => Auth::id(),
                        'uploaded_at' => now(),
                    ]);

                    Log::info('Google Drive cassing photo copied successfully', [
                        'lowering_id' => $lowering->id,
                        'drive_link' => $cassingDriveLink,
                        'result' => $result
                    ]);

                } catch (\Exception $e) {
                    Log::error('Failed to copy cassing photo from Google Drive link', [
                        'lowering_id' => $lowering->id,
                        'drive_link' => $cassingDriveLink,
                        'error' => $e->getMessage()
                    ]);
                    // Don't throw, just log - cassing photo is optional
                }
            }

            // Handle marker tape photo from Google Drive link (if provided)
            if ($request->filled('foto_evidence_marker_tape_link') && $request->has('aksesoris_marker_tape')) {
                $markerTapeDriveLink = $request->input('foto_evidence_marker_tape_link');

                try {
                    $googleDriveService = app(GoogleDriveService::class);

                    $lineNumber = $lowering->lineNumber->line_number;
                    $clusterName = $lowering->lineNumber->cluster->nama_cluster;
                    $tanggalFolder = \Carbon\Carbon::parse($lowering->tanggal_jalur)->format('Y-m-d');
                    $customDrivePath = "JALUR_LOWERING/{$clusterName}/{$lineNumber}/{$tanggalFolder}";

                    // Generate descriptive filename
                    $waktu = date('H-i-s');
                    $fieldSlug = 'marker-tape';
                    $customFileName = "LOWERING_{$lineNumber}_{$tanggalFolder}_{$waktu}_{$fieldSlug}";

                    $result = $googleDriveService->copyFromDriveLink(
                        $markerTapeDriveLink,
                        $customDrivePath,
                        $customFileName
                    );

                    PhotoApproval::create([
                        'reff_id_pelanggan' => null,
                        'module_name' => 'jalur_lowering',
                        'module_record_id' => $lowering->id,
                        'photo_field_name' => 'foto_evidence_marker_tape',
                        'photo_url' => $result['url'],
                        'drive_link' => $result['url'],
                        'photo_status' => 'tracer_pending',
                        'uploaded_by' => Auth::id(),
                        'uploaded_at' => now(),
                    ]);

                    Log::info('Google Drive marker tape photo copied successfully', [
                        'lowering_id' => $lowering->id,
                        'drive_link' => $markerTapeDriveLink,
                        'result' => $result
                    ]);

                } catch (\Exception $e) {
                    Log::error('Failed to copy marker tape photo from Google Drive link', [
                        'lowering_id' => $lowering->id,
                        'drive_link' => $markerTapeDriveLink,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Handle concrete slab photo from Google Drive link (if provided)
            if ($request->filled('foto_evidence_concrete_slab_link') && $request->has('aksesoris_concrete_slab')) {
                $concreteSlabDriveLink = $request->input('foto_evidence_concrete_slab_link');

                try {
                    $googleDriveService = app(GoogleDriveService::class);

                    $lineNumber = $lowering->lineNumber->line_number;
                    $clusterName = $lowering->lineNumber->cluster->nama_cluster;
                    $tanggalFolder = \Carbon\Carbon::parse($lowering->tanggal_jalur)->format('Y-m-d');
                    $customDrivePath = "JALUR_LOWERING/{$clusterName}/{$lineNumber}/{$tanggalFolder}";

                    // Generate descriptive filename
                    $waktu = date('H-i-s');
                    $fieldSlug = 'concrete-slab';
                    $customFileName = "LOWERING_{$lineNumber}_{$tanggalFolder}_{$waktu}_{$fieldSlug}";

                    $result = $googleDriveService->copyFromDriveLink(
                        $concreteSlabDriveLink,
                        $customDrivePath,
                        $customFileName
                    );

                    PhotoApproval::create([
                        'reff_id_pelanggan' => null,
                        'module_name' => 'jalur_lowering',
                        'module_record_id' => $lowering->id,
                        'photo_field_name' => 'foto_evidence_concrete_slab',
                        'photo_url' => $result['url'],
                        'drive_link' => $result['url'],
                        'photo_status' => 'tracer_pending',
                        'uploaded_by' => Auth::id(),
                        'uploaded_at' => now(),
                    ]);

                    Log::info('Google Drive concrete slab photo copied successfully', [
                        'lowering_id' => $lowering->id,
                        'drive_link' => $concreteSlabDriveLink,
                        'result' => $result
                    ]);

                } catch (\Exception $e) {
                    Log::error('Failed to copy concrete slab photo from Google Drive link', [
                        'lowering_id' => $lowering->id,
                        'drive_link' => $concreteSlabDriveLink,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('jalur.lowering.show', $lowering)
                ->with('success', 'Data lowering berhasil dibuat.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Gagal menyimpan data lowering: ' . $e->getMessage());
        }
    }

    public function show(JalurLoweringData $lowering)
    {
        $lowering->load([
            'lineNumber.cluster',
            'tracerApprover',
            'cgpApprover',
            'createdBy',
            'updatedBy',
            'photoApprovals'
        ]);

        $photoSlots = $lowering->getSlotCompletionStatus();

        return view('jalur.lowering.show', compact('lowering', 'photoSlots'));
    }

    public function edit(JalurLoweringData $lowering)
    {
        if (!in_array($lowering->status_laporan, ['draft', 'revisi_tracer', 'revisi_cgp'])) {
            return back()->with('error', 'Data lowering ini tidak dapat diedit.');
        }

        $clusters = JalurCluster::active()->get();
        $lineNumbers = JalurLineNumber::active()->with('cluster')->get();

        return view('jalur.lowering.edit', compact('lowering', 'clusters', 'lineNumbers'));
    }

    public function update(Request $request, JalurLoweringData $lowering)
    {
        if (!in_array($lowering->status_laporan, ['draft', 'revisi_tracer', 'revisi_cgp'])) {
            return back()->with('error', 'Data lowering ini tidak dapat diedit.');
        }

        // Map crossing inputs to standard names if Tipe Bongkaran is Crossing/Zinker
        // This prevents collision with Open Cut inputs in the DOM
        if (in_array($request->input('tipe_bongkaran'), ['Crossing', 'Zinker'])) {
            if ($request->has('aksesoris_cassing_crossing')) {
                $request->merge(['aksesoris_cassing' => $request->input('aksesoris_cassing_crossing')]);
            }
            if ($request->filled('cassing_quantity_crossing')) {
                $request->merge(['cassing_quantity' => $request->input('cassing_quantity_crossing')]);
            }
            if ($request->filled('cassing_type_crossing')) {
                $request->merge(['cassing_type' => $request->input('cassing_type_crossing')]);
            }
            if ($request->hasFile('foto_evidence_cassing_crossing')) {
                $request->files->set('foto_evidence_cassing', $request->file('foto_evidence_cassing_crossing'));
            }
        }

        // Determine upload method and create conditional validation rules
        $uploadMethod = $request->input('upload_method', 'file');

        $validationRules = [
            'tanggal_jalur' => 'required|date',
            'tipe_bongkaran' => 'required|in:Manual Boring,Open Cut,Crossing,Zinker,HDD,Manual Boring - PK,Crossing - PK',
            'tipe_material' => 'nullable|in:Aspal,Tanah,Paving,Beton',
            'penggelaran' => 'required|numeric|min:0.01',
            'bongkaran' => 'required|numeric|min:0.01',
            'kedalaman_lowering' => 'required|numeric|min:1',
            'aksesoris_cassing' => 'boolean',
            'aksesoris_marker_tape' => 'boolean',
            'aksesoris_concrete_slab' => 'boolean',
            'marker_tape_quantity' => 'required_if:aksesoris_marker_tape,1|nullable|numeric|min:0.1',
            'concrete_slab_quantity' => 'required_if:aksesoris_concrete_slab,1|nullable|integer|min:1',
            'cassing_quantity' => 'required_if:aksesoris_cassing,1|nullable|numeric|min:0.1',
            'cassing_type' => 'required_if:aksesoris_cassing,1|nullable|in:4_inch,8_inch',
            'keterangan' => 'nullable|string|max:1000',
        ];

        // Add conditional validation for photo updates (not required in edit)
        if ($uploadMethod === 'file') {
            $validationRules['foto_evidence_penggelaran_bongkaran'] = 'nullable|image|mimes:jpeg,jpg,png|max:35840';
        } else {
            $validationRules['foto_evidence_penggelaran_bongkaran_link'] = 'nullable|url';
        }

        $validated = $request->validate($validationRules);

        // Remove file/link fields from validated data (handled separately)
        unset($validated['foto_evidence_penggelaran_bongkaran']);
        unset($validated['foto_evidence_penggelaran_bongkaran_link']);

        $validated['updated_by'] = Auth::id();

        try {
            DB::beginTransaction();

            $lowering->update($validated);

            // Handle photo updates if new photos are uploaded
            if ($request->hasFile('foto_evidence_penggelaran_bongkaran') || $request->filled('foto_evidence_penggelaran_bongkaran_link')) {
                if ($uploadMethod === 'file') {
                    // Handle photo uploads - multiple photos based on accessories
                    $photoFields = ['foto_evidence_penggelaran_bongkaran']; // Always required

                    // Add accessory photos based on tipe_bongkaran and checkboxes
                    if ($validated['tipe_bongkaran'] === 'Open Cut') {
                        if ($request->hasFile('foto_evidence_marker_tape')) {
                            $photoFields[] = 'foto_evidence_marker_tape';
                        }
                        if ($request->hasFile('foto_evidence_concrete_slab')) {
                            $photoFields[] = 'foto_evidence_concrete_slab';
                        }
                        if ($request->has('aksesoris_cassing') && $request->hasFile('foto_evidence_cassing')) {
                            $photoFields[] = 'foto_evidence_cassing';
                        }
                    } elseif (in_array($validated['tipe_bongkaran'], ['Crossing', 'Zinker'])) {
                        if ($request->has('aksesoris_cassing') && $request->hasFile('foto_evidence_cassing')) {
                            $photoFields[] = 'foto_evidence_cassing';
                        }
                    }

                    $this->handlePhotoUploads($request, $lowering, $photoFields);
                } else {
                    // Handle Google Drive link for main photo
                    $driveLink = $request->input('foto_evidence_penggelaran_bongkaran_link');

                    try {
                        $googleDriveService = app(GoogleDriveService::class);

                        // Use same path structure as direct upload
                        $lineNumber = $lowering->lineNumber->line_number;
                        $clusterName = $lowering->lineNumber->cluster->nama_cluster;
                        $clusterSlug = \Illuminate\Support\Str::slug($clusterName, '_');
                        $tanggalFolder = \Carbon\Carbon::parse($lowering->tanggal_jalur)->format('Y-m-d');
                        $customDrivePath = "jalur_lowering/{$clusterSlug}/{$lineNumber}/{$tanggalFolder}";

                        // Generate descriptive filename
                        $waktu = date('H-i-s');
                        $fieldSlug = 'penggelaran-bongkaran';
                        $customFileName = "LOWERING_{$lineNumber}_{$tanggalFolder}_{$waktu}_{$fieldSlug}";

                        $result = $googleDriveService->copyFromDriveLink(
                            $driveLink,
                            $customDrivePath,
                            $customFileName
                        );

                        // Replace existing photo or create new one
                        $existingPhoto = PhotoApproval::where('module_name', 'jalur_lowering')
                            ->where('module_record_id', $lowering->id)
                            ->where('photo_field_name', 'foto_evidence_penggelaran_bongkaran')
                            ->first();

                        $photoData = [
                            'reff_id_pelanggan' => null, // Jalur doesn't have pelanggan
                            'module_name' => 'jalur_lowering',
                            'module_record_id' => $lowering->id,
                            'photo_field_name' => 'foto_evidence_penggelaran_bongkaran',
                            'photo_url' => $result['url'],
                            'drive_link' => $result['url'],
                            'photo_status' => 'tracer_pending', // Reset to pending when replaced
                            'uploaded_by' => Auth::id(),
                            'uploaded_at' => now(),
                            // Reset approval fields when photo is replaced
                            'tracer_user_id' => null,
                            'tracer_approved_at' => null,
                            'tracer_rejected_at' => null,
                            'tracer_notes' => null,
                            'cgp_user_id' => null,
                            'cgp_approved_at' => null,
                            'cgp_rejected_at' => null,
                            'cgp_notes' => null,
                        ];

                        if ($existingPhoto) {
                            $existingPhoto->update($photoData);
                            // Reset module status to draft when photo is replaced
                            $this->resetModuleStatusWhenPhotoReplaced($lowering);
                        } else {
                            PhotoApproval::create($photoData);
                        }

                        Log::info('Google Drive photo copied successfully in update', [
                            'lowering_id' => $lowering->id,
                            'drive_link' => $driveLink,
                            'result' => $result
                        ]);

                    } catch (\Exception $e) {
                        Log::error('Failed to copy photo from Google Drive link in update', [
                            'lowering_id' => $lowering->id,
                            'drive_link' => $driveLink,
                            'error' => $e->getMessage()
                        ]);
                        throw new \Exception('Gagal mengunduh foto dari Google Drive: ' . $e->getMessage());
                    }
                }
            }

            // Update line number totals (handled by model events)

            DB::commit();

            return redirect()
                ->route('jalur.lowering.show', $lowering)
                ->with('success', 'Data lowering berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui data lowering: ' . $e->getMessage());
        }
    }

    public function destroy(JalurLoweringData $lowering)
    {
        if (!in_array($lowering->status_laporan, ['draft'])) {
            return back()->with('error', 'Hanya data lowering dengan status draft yang dapat dihapus.');
        }

        try {
            DB::beginTransaction();

            // Delete related photos
            PhotoApproval::where('module_name', 'jalur_lowering')
                ->where('module_record_id', $lowering->id)
                ->delete();

            $lowering->delete();

            DB::commit();

            return redirect()
                ->route('jalur.lowering.index')
                ->with('success', 'Data lowering berhasil dihapus.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menghapus data lowering: ' . $e->getMessage());
        }
    }

    // Approval methods
    public function approveByTracer(Request $request, JalurLoweringData $lowering)
    {
        if (!$lowering->canApproveByTracer()) {
            return back()->with('error', 'Data lowering ini tidak dapat di-approve oleh tracer.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        if ($lowering->approveByTracer(Auth::id(), $validated['notes'] ?? null)) {
            return back()->with('success', 'Data lowering berhasil di-approve oleh tracer.');
        }

        return back()->with('error', 'Gagal approve data lowering.');
    }

    public function rejectByTracer(Request $request, JalurLoweringData $lowering)
    {
        $validated = $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $lowering->update([
            'status_laporan' => 'revisi_tracer',
            'tracer_notes' => $validated['notes'],
            'updated_by' => Auth::id(),
        ]);

        return back()->with('success', 'Data lowering dikembalikan untuk revisi.');
    }

    public function approveByCgp(Request $request, JalurLoweringData $lowering)
    {
        if (!$lowering->canApproveByCgp()) {
            return back()->with('error', 'Data lowering ini tidak dapat di-approve oleh CGP.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        if ($lowering->approveByCgp(Auth::id(), $validated['notes'] ?? null)) {
            return back()->with('success', 'Data lowering berhasil di-approve oleh CGP.');
        }

        return back()->with('error', 'Gagal approve data lowering.');
    }

    public function rejectByCgp(Request $request, JalurLoweringData $lowering)
    {
        $validated = $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $lowering->update([
            'status_laporan' => 'revisi_cgp',
            'cgp_notes' => $validated['notes'],
            'updated_by' => Auth::id(),
        ]);

        return back()->with('success', 'Data lowering dikembalikan untuk revisi.');
    }

    // Photo upload methods
    public function uploadPhoto(Request $request, JalurLoweringData $lowering)
    {
        $validated = $request->validate([
            'photo' => 'required|image|max:5120', // 5MB
            'photo_field_name' => 'required|in:foto_evidence_penggelaran_bongkaran,foto_evidence_kedalaman_lowering,foto_evidence_marker_tape,foto_evidence_concrete_slab,foto_evidence_cassing',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $file = $request->file('photo');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = "jalur/lowering/{$lowering->lineNumber->line_number}/" . $fileName;

            Storage::disk('public')->put($path, file_get_contents($file));

            // Create photo approval record dengan PhotoApprovalService untuk AI processing
            $photoApprovalService = app(PhotoApprovalService::class);
            $photoApprovalService->processAIValidation(
                $lowering->lineNumber->line_number, // Using line_number as reffId
                'jalur_lowering',
                $validated['photo_field_name'],
                Storage::url($path),
                Auth::id()
            );

            return back()->with('success', 'Foto berhasil diupload.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal upload foto: ' . $e->getMessage());
        }
    }

    // API endpoints
    public function getLineNumbers(Request $request)
    {
        $clusterId = $request->get('cluster_id');

        if (!$clusterId) {
            return response()->json([]);
        }

        $lineNumbers = JalurLineNumber::byCluster($clusterId)
            ->active()
            ->select(['id', 'line_number', 'diameter', 'status_line', 'estimasi_panjang', 'total_penggelaran'])
            ->orderBy('line_number')
            ->get();

        return response()->json($lineNumbers);
    }

    private function handlePhotoUploads(Request $request, JalurLoweringData $lowering, array $photoFields): void
    {
        $fileUploadService = app(FileUploadService::class);

        foreach ($photoFields as $fieldName) {
            if ($request->hasFile($fieldName)) {
                $file = $request->file($fieldName);

                $lineNumber = $lowering->lineNumber->line_number;
                $namaJalan = $lowering->nama_jalan;

                // Generate descriptive filename
                $waktu = date('H-i-s');
                $tanggalFolder = \Carbon\Carbon::parse($lowering->tanggal_jalur)->format('Y-m-d');
                $fieldSlug = str_replace(['foto_evidence_', '_'], ['', '-'], $fieldName);
                $customFileName = "LOWERING_{$lineNumber}_{$tanggalFolder}_{$waktu}_{$fieldSlug}";

                try {
                    // Custom upload untuk struktur: jalur_lowering/cluster_slug/LineNumber/Date/
                    $googleDriveService = app(GoogleDriveService::class);

                    // Buat path custom: jalur_lowering/karanggayam/63-KRG-LN001/2025-09-06/
                    $clusterName = $lowering->lineNumber->cluster->nama_cluster; // Karanggayam
                    $clusterSlug = \Illuminate\Support\Str::slug($clusterName, '_');
                    $customDrivePath = "jalur_lowering/{$clusterSlug}/{$lineNumber}/{$tanggalFolder}";

                    // Upload dengan custom path
                    $uploadResult = $this->uploadToCustomDrivePath(
                        $googleDriveService,
                        $file,
                        $customDrivePath,
                        $customFileName
                    );

                    // Replace existing photo or create new one
                    $existingPhoto = PhotoApproval::where('module_name', 'jalur_lowering')
                        ->where('module_record_id', $lowering->id)
                        ->where('photo_field_name', $fieldName)
                        ->first();

                    $photoData = [
                        'reff_id_pelanggan' => null, // Jalur doesn't have pelanggan
                        'module_name' => 'jalur_lowering',
                        'module_record_id' => $lowering->id,
                        'photo_field_name' => $fieldName,
                        'photo_url' => $uploadResult['url'] ?? $uploadResult['path'],
                        'storage_path' => $uploadResult['path'] ?? null,
                        'drive_file_id' => $uploadResult['drive_file_id'] ?? null,
                        'drive_link' => $uploadResult['url'] ?? null,
                        'photo_status' => 'tracer_pending', // Reset to pending when replaced
                        'uploaded_by' => Auth::id(),
                        'uploaded_at' => now(),
                        // Reset approval fields when photo is replaced
                        'tracer_user_id' => null,
                        'tracer_approved_at' => null,
                        'tracer_rejected_at' => null,
                        'tracer_notes' => null,
                        'cgp_user_id' => null,
                        'cgp_approved_at' => null,
                        'cgp_notes' => null,
                    ];

                    if ($existingPhoto) {
                        $existingPhoto->update($photoData);
                        // Reset module status to draft when photo is replaced
                        $this->resetModuleStatusWhenPhotoReplaced($lowering);
                    } else {
                        PhotoApproval::create($photoData);
                    }

                } catch (\Exception $e) {
                    // Log error untuk debugging
                    Log::error("Google Drive upload failed for lowering: " . $e->getMessage());

                    // Fallback ke manual storage dengan struktur yang diinginkan
                    $clusterCode = $lowering->lineNumber->cluster->code_cluster;
                    $customPath = "jalur-lowering/{$clusterCode}/{$lineNumber}/{$tanggalFolder}";
                    $fileName = $customFileName . '.' . strtolower($file->getClientOriginalExtension() ?: 'jpg');
                    $fullPath = $customPath . '/' . $fileName;

                    // Simpan ke public storage agar bisa diakses
                    $publicPath = public_path('storage/' . $fullPath);
                    $directory = dirname($publicPath);
                    if (!file_exists($directory)) {
                        mkdir($directory, 0755, true);
                    }
                    move_uploaded_file($file->getPathname(), $publicPath);

                    // Backup ke storage/app/public
                    Storage::disk('public')->put($fullPath, file_get_contents($publicPath));

                    // Replace existing photo or create new one
                    $existingPhoto = PhotoApproval::where('module_name', 'jalur_lowering')
                        ->where('module_record_id', $lowering->id)
                        ->where('photo_field_name', $fieldName)
                        ->first();

                    $photoData = [
                        'reff_id_pelanggan' => null, // Jalur doesn't have pelanggan
                        'module_name' => 'jalur_lowering',
                        'module_record_id' => $lowering->id,
                        'photo_field_name' => $fieldName,
                        'photo_url' => Storage::url($fullPath),
                        'storage_path' => $fullPath,
                        'drive_file_id' => null, // Local storage fallback doesn't use Drive
                        'photo_status' => 'tracer_pending', // Reset to pending when replaced
                        'uploaded_by' => Auth::id(),
                        'uploaded_at' => now(),
                        // Reset approval fields when photo is replaced
                        'tracer_user_id' => null,
                        'tracer_approved_at' => null,
                        'tracer_rejected_at' => null,
                        'tracer_notes' => null,
                        'cgp_user_id' => null,
                        'cgp_approved_at' => null,
                        'cgp_notes' => null,
                    ];

                    if ($existingPhoto) {
                        $existingPhoto->update($photoData);
                        // Reset module status to draft when photo is replaced
                        $this->resetModuleStatusWhenPhotoReplaced($lowering);
                    } else {
                        PhotoApproval::create($photoData);
                    }
                }
            }
        }
    }

    private function resetModuleStatusWhenPhotoReplaced(JalurLoweringData $lowering): void
    {
        // If module status is beyond draft, reset it to draft when photo is replaced
        // This ensures consistency between photo status and module status
        if (!in_array($lowering->status_laporan, ['draft'])) {
            $lowering->update([
                'status_laporan' => 'draft',
                'tracer_approved_by' => null,
                'tracer_approved_at' => null,
                'tracer_notes' => null,
                'cgp_approved_by' => null,
                'cgp_approved_at' => null,
                'cgp_notes' => null,
                'updated_by' => Auth::id()
            ]);

            Log::info('Module status reset to draft due to photo replacement', [
                'lowering_id' => $lowering->id,
                'line_number' => $lowering->lineNumber->line_number,
                'user_id' => Auth::id()
            ]);
        }
    }

    private function uploadToCustomDrivePath($googleDriveService, $file, $customPath, $fileName)
    {
        // Gunakan ensureNestedFolders untuk membuat struktur folder secara otomatis
        $folderId = $googleDriveService->ensureNestedFolders($customPath);

        // Upload file ke folder terakhir
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $fullFileName = $fileName . '.' . $ext;

        $uploadResult = $googleDriveService->uploadFile($file, $folderId, $fullFileName);

        return [
            'path' => $customPath . '/' . $fullFileName,
            'drive_file_id' => $uploadResult['id'] ?? null,
            'url' => $uploadResult['webViewLink'] ?? $uploadResult['webContentLink'] ?? null
        ];
    }

    /**
     * Check line number availability (for real-time validation)
     */
    public function checkLineNumberAvailability(Request $request)
    {
        $lineNumber = $request->get('line_number');
        $clusterId = $request->get('cluster_id');

        if (empty($lineNumber)) {
            return response()->json([
                'error' => 'Line number is required'
            ], 400);
        }

        // Check if line number already exists
        $existingLineNumber = JalurLineNumber::where('line_number', $lineNumber)->first();

        $isAvailable = true;
        $statusMessage = 'Line number tersedia dan dapat digunakan';
        $statusClass = 'success';

        if ($existingLineNumber) {
            if ($clusterId && $existingLineNumber->cluster_id != $clusterId) {
                // Line number exists in different cluster
                $isAvailable = false;
                $statusMessage = "Line Number {$lineNumber} sudah digunakan di cluster {$existingLineNumber->cluster->nama_cluster}. Gunakan nomor lain.";
                $statusClass = 'error';
            } else {
                // Line number exists in same cluster - can be reused
                $isAvailable = true;
                $statusMessage = "Line Number {$lineNumber} sudah ada di cluster ini dan akan digunakan.";
                $statusClass = 'info';
            }
        }

        return response()->json([
            'is_available' => $isAvailable,
            'line_number' => $lineNumber,
            'status_message' => $statusMessage,
            'status_class' => $statusClass,
            'existing_line' => $existingLineNumber ? [
                'id' => $existingLineNumber->id,
                'line_number' => $existingLineNumber->line_number,
                'cluster_name' => $existingLineNumber->cluster->nama_cluster ?? null,
                'created_at' => $existingLineNumber->created_at->format('Y-m-d H:i:s'),
            ] : null,
        ]);
    }
}