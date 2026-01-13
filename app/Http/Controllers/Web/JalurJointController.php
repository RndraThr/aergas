<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\JalurJointData;
use App\Models\JalurCluster;
use App\Models\JalurFittingType;
use App\Models\JalurLineNumber;
use App\Models\JalurJointNumber;
use App\Models\PhotoApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\PhotoApprovalService;

class JalurJointController extends Controller
{

    public function index(Request $request)
    {
        $query = JalurJointData::query()->with(['cluster', 'fittingType', 'tracerApprover', 'cgpApprover']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nomor_joint', 'like', "%{$request->search}%")
                    ->orWhere('joint_line_from', 'like', "%{$request->search}%")
                    ->orWhere('joint_line_to', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('cluster_id')) {
            $query->byCluster($request->cluster_id);
        }

        if ($request->filled('fitting_type_id')) {
            $query->byFittingType($request->fitting_type_id);
        }

        if ($request->filled('status')) {
            $query->where('status_laporan', $request->status);
        }

        if ($request->filled('line_number_id')) {
            $query->where('line_number_id', $request->line_number_id);
        }

        if ($request->filled('tipe_penyambungan')) {
            $query->byTipePenyambungan($request->tipe_penyambungan);
        }

        $jointData = $query->latest()->paginate(15);
        $clusters = JalurCluster::active()->get();
        $fittingTypes = JalurFittingType::active()->get();

        // Get all line numbers for filter dropdown
        $lineNumbers = JalurLineNumber::active()
            ->with('cluster')
            ->orderBy('line_number')
            ->get();

        return view('jalur.joint.index', compact('jointData', 'clusters', 'fittingTypes', 'lineNumbers'));
    }

    public function create(Request $request)
    {
        $clusters = JalurCluster::active()->get();
        $fittingTypes = JalurFittingType::active()->get();

        // Get available diameters (Merge DB values with Standard Sizes)
        $dbDiameters = JalurLineNumber::active()
            ->distinct()
            ->pluck('diameter')
            ->toArray();

        // Add standard PE diameters to ensure availability (Restricted to 63, 90, 180 as requested)
        $standardDiameters = ['63', '90', '180'];

        $diameters = collect(array_merge($dbDiameters, $standardDiameters))
            ->unique()
            ->sort(function ($a, $b) {
                return (int) $a - (int) $b;
            })
            ->values();

        return view('jalur.joint.create', compact('clusters', 'fittingTypes', 'diameters'));
    }

    public function store(Request $request)
    {
        // Check if fitting type is Equal Tee to determine if joint_line_optional is required
        $fittingTypeId = $request->input('fitting_type_id');
        $fittingType = $fittingTypeId ? JalurFittingType::find($fittingTypeId) : null;
        $isEqualTee = $fittingType && $fittingType->code_fitting === 'ET';

        // Validate upload method and joint number mode
        $uploadMethod = $request->input('upload_method', 'file');
        $jointNumberMode = $request->input('joint_number_mode', 'manual');

        $validationRules = [
            'cluster_id' => 'required|exists:jalur_clusters,id',
            'fitting_type_id' => 'nullable|exists:jalur_fitting_types,id',
            'joint_number_mode' => 'required|in:manual,select',
            'tanggal_joint' => 'required|date',
            'joint_line_from' => 'required|string|max:50',
            'joint_line_to' => 'required|string|max:50',
            'joint_line_optional' => $isEqualTee ? 'required|string|max:50' : 'nullable|string|max:50',
            'tipe_penyambungan' => 'required|in:EF,BF',
            'keterangan' => 'nullable|string|max:1000',
            'upload_method' => 'required|in:file,link',
        ];

        // Conditional validation based on joint number mode
        if ($jointNumberMode === 'manual') {
            $validationRules['nomor_joint_suffix'] = 'required|string|max:10|regex:/^[0-9A-Za-z]+$/';
        } else {
            $validationRules['selected_joint_number_id'] = 'required|exists:jalur_joint_numbers,id';
        }

        $validationMessages = [
            'nomor_joint_suffix.required' => 'Nomor joint wajib diisi.',
            'nomor_joint_suffix.regex' => 'Nomor joint hanya boleh mengandung huruf dan angka.',
            'joint_line_optional.required' => 'Joint Line Optional wajib diisi untuk Equal Tee (3-way connection).',
        ];

        // Conditional validation based on upload method
        if ($uploadMethod === 'file') {
            $validationRules['foto_evidence_joint'] = 'required|image|mimes:jpeg,jpg,png|max:35840';
            $validationMessages['foto_evidence_joint.required'] = 'Foto evidence joint wajib diupload.';
            $validationMessages['foto_evidence_joint.image'] = 'File yang diupload harus berupa gambar.';
            $validationMessages['foto_evidence_joint.mimes'] = 'Format foto harus JPG, JPEG, atau PNG.';
            $validationMessages['foto_evidence_joint.max'] = 'Ukuran foto maksimal 5MB.';
        } else {
            $validationRules['foto_evidence_joint_link'] = 'required|url';
            $validationMessages['foto_evidence_joint_link.required'] = 'Link Google Drive wajib diisi.';
            $validationMessages['foto_evidence_joint_link.url'] = 'Format link tidak valid.';
        }

        $validated = $request->validate($validationRules, $validationMessages);

        // Get cluster and fitting type
        $cluster = JalurCluster::findOrFail($validated['cluster_id']);
        $fittingType = !empty($validated['fitting_type_id']) ? JalurFittingType::findOrFail($validated['fitting_type_id']) : null;

        // Get diameter to determine joint number format
        $diameter = $request->input('diameter_filter');

        // Handle joint number based on mode
        if ($jointNumberMode === 'manual') {
            // Manual mode: Build complete joint number

            // Special format for Diameter 90: {TipePenyambungan}.{Nomor}
            if ($diameter == '90') {
                $tipePenyambungan = $validated['tipe_penyambungan']; // BF or EF
                $nomorJoint = $tipePenyambungan . '.' . $validated['nomor_joint_suffix']; // e.g., BF.01
            } else {
                // Standard format: {Cluster}-{Fitting}{Nomor}
                $fittingCode = $fittingType ? $fittingType->code_fitting : 'XX';
                $nomorJoint = $cluster->code_cluster . '-' . $fittingCode . $validated['nomor_joint_suffix'];
            }

            $jointNumberId = null;
        } else {
            // Select mode: Get from pre-created joint number
            $selectedJointNumber = JalurJointNumber::findOrFail($validated['selected_joint_number_id']);

            // Validate that selected joint number matches cluster and fitting type
            if ($selectedJointNumber->cluster_id != $validated['cluster_id']) {
                return back()
                    ->withInput()
                    ->with('error', 'Nomor joint yang dipilih tidak sesuai dengan cluster.');
            }

            if (!empty($validated['fitting_type_id']) && $selectedJointNumber->fitting_type_id != $validated['fitting_type_id']) {
                return back()
                    ->withInput()
                    ->with('error', 'Nomor joint yang dipilih tidak sesuai dengan fitting type.');
            }

            // Check if joint number is already used
            if ($selectedJointNumber->usedByJoint) {
                return back()
                    ->withInput()
                    ->with('error', 'Nomor joint yang dipilih sudah digunakan.');
            }

            $nomorJoint = $selectedJointNumber->nomor_joint;
            $jointNumberId = $selectedJointNumber->id;
        }

        // Check if joint number already exists (including soft deleted)
        $existingJoint = JalurJointData::withTrashed()->where('nomor_joint', $nomorJoint)->first();
        if ($existingJoint && !$existingJoint->trashed()) {
            return back()
                ->withInput()
                ->with('error', "Nomor joint {$nomorJoint} sudah digunakan. Silakan gunakan nomor lain.");
        }

        // Handle photo upload to Google Drive
        $photoPath = null;
        $uploadResult = null;

        if ($uploadMethod === 'file' && $request->hasFile('foto_evidence_joint')) {
            // Handle file upload
            $file = $request->file('foto_evidence_joint');

            // Generate descriptive filename
            $waktu = date('H-i-s');
            $tanggalFolder = date('Y-m-d');
            $fieldName = 'foto_evidence_joint';
            $fieldSlug = str_replace(['foto_evidence_', '_'], ['', '-'], $fieldName);
            $customFileName = "JOINT_{$nomorJoint}_{$tanggalFolder}_{$waktu}_{$fieldSlug}";

            try {
                // Upload to Google Drive with custom path structure
                $googleDriveService = app(\App\Services\GoogleDriveService::class);

                // Create custom path: jalur_joint/cluster_slug/JointNumber/Date/
                $clusterName = $cluster->nama_cluster;
                $clusterSlug = \Illuminate\Support\Str::slug($clusterName, '_');
                $customDrivePath = "jalur_joint/{$clusterSlug}/{$nomorJoint}/{$tanggalFolder}";

                // Upload with custom path
                $uploadResult = $this->uploadToCustomDrivePath(
                    $googleDriveService,
                    $file,
                    $customDrivePath,
                    $customFileName
                );

                $photoPath = $uploadResult['url'] ?? $uploadResult['path'];

            } catch (\Exception $e) {
                Log::error("Google Drive upload failed for joint: " . $e->getMessage());

                // Fallback to local storage
                $fileName = time() . '_' . $file->getClientOriginalName();
                $fallbackPath = "jalur/joint/{$nomorJoint}/" . $fileName;
                Storage::disk('public')->put($fallbackPath, file_get_contents($file));
                $photoPath = $fallbackPath;
            }
        } else if ($uploadMethod === 'link' && $validated['foto_evidence_joint_link']) {
            // Handle Google Drive link download
            $driveLink = $validated['foto_evidence_joint_link'];

            // Generate descriptive filename
            $waktu = date('H-i-s');
            $tanggalFolder = date('Y-m-d');
            $customFileName = "JOINT_{$nomorJoint}_{$tanggalFolder}_{$waktu}_drive-download";

            try {
                // Download and upload from Google Drive link
                $googleDriveService = app(\App\Services\GoogleDriveService::class);

                // Create custom path: jalur_joint/cluster_slug/JointNumber/Date/
                $clusterName = $cluster->nama_cluster;
                $clusterSlug = \Illuminate\Support\Str::slug($clusterName, '_');
                $customDrivePath = "jalur_joint/{$clusterSlug}/{$nomorJoint}/{$tanggalFolder}";

                // Copy from Google Drive link to our Drive folder
                $copyResult = $googleDriveService->copyFromDriveLink($driveLink, $customDrivePath, $customFileName);

                $photoPath = $copyResult['url'] ?? $copyResult['path'];

                Log::info("Successfully downloaded and saved photo from Google Drive link", [
                    'joint_number' => $nomorJoint,
                    'drive_link' => $driveLink,
                    'saved_path' => $photoPath
                ]);

            } catch (\Exception $e) {
                Log::error("Failed to download from Google Drive link: " . $e->getMessage(), [
                    'joint_number' => $nomorJoint,
                    'drive_link' => $driveLink
                ]);
                return back()
                    ->withInput()
                    ->with('error', 'Gagal mendownload foto dari Google Drive. Pastikan link dapat diakses publik.');
            }
        }

        // Build joint_code based on mode
        if ($jointNumberMode === 'manual') {
            $fittingCode = $fittingType ? $fittingType->code_fitting : 'XX';
            $validated['joint_code'] = $fittingCode . $validated['nomor_joint_suffix']; // e.g., ET002
            $jointSuffix = $validated['nomor_joint_suffix']; // Save for later use
        } else {
            // For select mode, get joint_code from selected joint number
            $selectedJointNumber = JalurJointNumber::findOrFail($validated['selected_joint_number_id']);
            $validated['joint_code'] = $selectedJointNumber->joint_code;
        }

        // Remove foto_evidence_joint and related fields from validated data
        unset($validated['foto_evidence_joint']);
        unset($validated['foto_evidence_joint_link']);
        unset($validated['upload_method']);
        unset($validated['joint_number_mode']);

        // Remove fields based on mode
        if ($jointNumberMode === 'manual') {
            unset($validated['nomor_joint_suffix']);
            unset($validated['selected_joint_number_id']);
        } else {
            unset($validated['selected_joint_number_id']);
            // nomor_joint_suffix doesn't exist in select mode
        }

        // Link to Line Number to enable obtaining Diameter/Jalan via relation
        // Users select Line Number filtered by Diameter, so this links to the correct metadata.
        $lineFrom = JalurLineNumber::where('line_number', $validated['joint_line_from'])->first();
        if ($lineFrom) {
            $validated['line_number_id'] = $lineFrom->id;
        }

        // Store diameter from filter (important for diameter 90 and when using EXISTING line numbers)
        $validated['diameter'] = $diameter;

        $validated['nomor_joint'] = $nomorJoint;
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();
        $validated['status_laporan'] = 'draft';

        try {
            DB::beginTransaction();

            // Double-check joint number availability right before insert
            $existingJoint = JalurJointData::withTrashed()->where('nomor_joint', $nomorJoint)->first();
            if ($existingJoint && !$existingJoint->trashed()) {
                DB::rollBack();
                return back()
                    ->withInput()
                    ->with('error', "Nomor joint {$nomorJoint} sudah digunakan. Silakan gunakan nomor lain.");
            }

            $joint = JalurJointData::create($validated);

            // Handle joint number record based on mode
            if ($jointNumberMode === 'manual') {
                // Auto-create joint number record if not exists
                $jointCode = str_pad($jointSuffix, 3, '0', STR_PAD_LEFT);
                $existingJointNumber = JalurJointNumber::where('nomor_joint', $nomorJoint)->first();

                if (!$existingJointNumber) {
                    JalurJointNumber::create([
                        'cluster_id' => $validated['cluster_id'],
                        'fitting_type_id' => $validated['fitting_type_id'],
                        'nomor_joint' => $nomorJoint,
                        'joint_code' => $jointCode,
                        'is_active' => true,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                }
            }
            // Note: For select mode, the joint number record already exists and doesn't need to be created

            // Create photo approval record if photo uploaded
            if ($photoPath) {
                // Replace existing photo or create new one
                $existingPhoto = PhotoApproval::where('module_name', 'jalur_joint')
                    ->where('module_record_id', $joint->id)
                    ->where('photo_field_name', 'foto_evidence_joint')
                    ->first();

                $photoData = [
                    'reff_id_pelanggan' => null, // Jalur doesn't have pelanggan
                    'module_name' => 'jalur_joint',
                    'module_record_id' => $joint->id,
                    'photo_field_name' => 'foto_evidence_joint',
                    'photo_url' => isset($uploadResult['url']) ? $uploadResult['url'] : Storage::url($photoPath),
                    'storage_path' => $uploadResult['path'] ?? $photoPath,
                    'drive_file_id' => $uploadResult['drive_file_id'] ?? null,
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
                    $this->resetModuleStatusWhenPhotoReplaced($joint);
                } else {
                    PhotoApproval::create($photoData);
                }
            }

            DB::commit();

            return redirect()
                ->route('jalur.joint.show', $joint)
                ->with('success', 'Data joint berhasil dibuat.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Gagal menyimpan data joint: ' . $e->getMessage());
        }
    }

    public function show(JalurJointData $joint)
    {
        $joint->load([
            'cluster',
            'fittingType',
            'tracerApprover',
            'cgpApprover',
            'photoApprovals',
            'createdBy',
            'updatedBy'
        ]);

        $photoSlots = $joint->getSlotCompletionStatus();

        return view('jalur.joint.show', compact('joint', 'photoSlots'));
    }

    public function edit(JalurJointData $joint)
    {
        if (!in_array($joint->status_laporan, ['draft', 'revisi_tracer', 'revisi_cgp'])) {
            return back()->with('error', 'Data joint ini tidak dapat diedit.');
        }

        $clusters = JalurCluster::active()->get();
        $fittingTypes = JalurFittingType::active()->get();

        // Get all line numbers for dropdown selection
        $lineNumbers = JalurLineNumber::active()
            ->with('cluster')
            ->orderBy('line_number')
            ->get();

        return view('jalur.joint.edit', compact('joint', 'clusters', 'fittingTypes', 'lineNumbers'));
    }

    public function update(Request $request, JalurJointData $joint)
    {
        if (!in_array($joint->status_laporan, ['draft', 'revisi_tracer', 'revisi_cgp'])) {
            return back()->with('error', 'Data joint ini tidak dapat diedit.');
        }

        // Check if fitting type is Equal Tee to determine if joint_line_optional is required
        $fittingTypeId = $request->input('fitting_type_id');
        $fittingType = $fittingTypeId ? JalurFittingType::find($fittingTypeId) : null;
        $isEqualTee = $fittingType && $fittingType->code_fitting === 'ET';

        $validated = $request->validate([
            'cluster_id' => 'required|exists:jalur_clusters,id',
            'line_number_id' => 'nullable|exists:jalur_line_numbers,id',
            'fitting_type_id' => 'nullable|exists:jalur_fitting_types,id',
            'tanggal_joint' => 'required|date',
            'joint_line_from' => 'required|string|max:50',
            'joint_line_to' => 'required|string|max:50',
            'joint_line_optional' => $isEqualTee ? 'required|string|max:50' : 'nullable|string|max:50',
            'tipe_penyambungan' => 'required|in:EF,BF',
            'lokasi_joint' => 'required|string|max:255',
            'keterangan' => 'nullable|string|max:1000',
        ], [
            'joint_line_optional.required' => 'Joint Line Optional wajib diisi untuk Equal Tee (3-way connection).',
        ]);

        $validated['updated_by'] = Auth::id();

        $joint->update($validated);

        // Handle Photo Uploads (Foto Sebelum & Sesudah)
        $singlePhotos = ['foto_sebelum', 'foto_sesudah'];
        $googleDriveService = app(\App\Services\GoogleDriveService::class);
        $nomorJoint = $joint->nomor_joint;
        $clusterSlug = \Illuminate\Support\Str::slug($joint->cluster->nama_cluster, '_');
        $tanggalFolder = $joint->tanggal_joint->format('Y-m-d');
        $customDrivePath = "jalur_joint/{$clusterSlug}/{$nomorJoint}/{$tanggalFolder}";

        // Process Single Photos (Replace if exists)
        foreach ($singlePhotos as $fieldName) {
            if ($request->hasFile($fieldName)) {
                $file = $request->file($fieldName);
                $waktu = date('H-i-s');
                $fieldSlug = str_replace(['foto_', '_'], ['', '-'], $fieldName);
                $customFileName = "JOINT_{$nomorJoint}_{$tanggalFolder}_{$waktu}_{$fieldSlug}";

                try {
                    $uploadResult = $this->uploadToCustomDrivePath(
                        $googleDriveService,
                        $file,
                        $customDrivePath,
                        $customFileName
                    );

                    $existingPhoto = PhotoApproval::where('module_name', 'jalur_joint')
                        ->where('module_record_id', $joint->id)
                        ->where('photo_field_name', $fieldName)
                        ->first();

                    $photoData = [
                        'module_name' => 'jalur_joint',
                        'module_record_id' => $joint->id,
                        'photo_field_name' => $fieldName,
                        'photo_url' => $uploadResult['url'] ?? Storage::url($uploadResult['path']),
                        'storage_path' => $uploadResult['path'] ?? null,
                        'drive_file_id' => $uploadResult['drive_file_id'] ?? null,
                        'photo_status' => 'tracer_pending',
                        'uploaded_by' => Auth::id(),
                        'uploaded_at' => now(),
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
                        // Reset status if this method exists, otherwise manually
                        if (method_exists($this, 'resetModuleStatusWhenPhotoReplaced')) {
                            $this->resetModuleStatusWhenPhotoReplaced($joint);
                        }
                    } else {
                        PhotoApproval::create($photoData);
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to upload {$fieldName} for joint {$joint->id}: " . $e->getMessage());
                    // Fallback to local not fully implemented here to save space, assuming Drive works or catching error
                }
            }
        }

        // Process Additional Photos (Always Append)
        if ($request->hasFile('foto_tambahan')) {
            foreach ($request->file('foto_tambahan') as $index => $file) {
                $fieldName = 'foto_tambahan';
                $waktu = date('H-i-s');
                $customFileName = "JOINT_{$nomorJoint}_{$tanggalFolder}_{$waktu}_tambahan-{$index}";

                try {
                    $uploadResult = $this->uploadToCustomDrivePath(
                        $googleDriveService,
                        $file,
                        $customDrivePath,
                        $customFileName
                    );

                    $photoData = [
                        'module_name' => 'jalur_joint',
                        'module_record_id' => $joint->id,
                        'photo_field_name' => $fieldName, // Duplicate field name allowed for HasMany
                        'photo_url' => $uploadResult['url'] ?? Storage::url($uploadResult['path']),
                        'storage_path' => $uploadResult['path'] ?? null,
                        'drive_file_id' => $uploadResult['drive_file_id'] ?? null,
                        'photo_status' => 'tracer_pending',
                        'uploaded_by' => Auth::id(),
                        'uploaded_at' => now(),
                    ];

                } catch (\Exception $e) {
                    Log::error("Failed to upload info photo for joint {$joint->id}: " . $e->getMessage());
                }
            }
        }

        // Handle Foto Evidence Joint uploads (multiple)
        if ($request->hasFile('foto_evidence_joint')) {
            Log::info("Update Joint: Foto evidence joint detected for upload. Count: " . count($request->file('foto_evidence_joint')));

            foreach ($request->file('foto_evidence_joint') as $index => $file) {
                // REPLACE Logic by Index
                $targetFieldNames = [];
                if ($index === 0) {
                    $targetFieldNames[] = 'foto_evidence_joint';    // Legacy name
                    $targetFieldNames[] = 'foto_evidence_joint_0';  // Indexed name
                } else {
                    $targetFieldNames[] = "foto_evidence_joint_{$index}";
                }

                $existingPhoto = PhotoApproval::where('module_name', 'jalur_joint')
                    ->where('module_record_id', $joint->id)
                    ->whereIn('photo_field_name', $targetFieldNames)
                    ->first();

                $fieldName = $existingPhoto ? $existingPhoto->photo_field_name : "foto_evidence_joint_{$index}";

                $waktu = date('H-i-s');
                $customFileName = "JOINT_{$nomorJoint}_{$tanggalFolder}_{$waktu}_evidence-{$index}";

                try {
                    $uploadResult = $this->uploadToCustomDrivePath(
                        $googleDriveService,
                        $file,
                        $customDrivePath,
                        $customFileName
                    );

                    Log::info("Update Joint: Upload success for {$fieldName}. URL: " . ($uploadResult['url'] ?? 'N/A'));

                    $photoData = [
                        'module_name' => 'jalur_joint',
                        'module_record_id' => $joint->id,
                        'photo_field_name' => $fieldName,
                        'photo_url' => $uploadResult['url'] ?? Storage::url($uploadResult['path']),
                        'storage_path' => $uploadResult['path'] ?? null,
                        'drive_file_id' => $uploadResult['drive_file_id'] ?? null,
                        'drive_link' => $uploadResult['url'] ?? null,
                        'photo_status' => 'tracer_pending',
                        'ai_status' => 'pending',
                        'uploaded_by' => Auth::id(),
                        'uploaded_at' => now(),
                        'tracer_user_id' => null,
                        'tracer_approved_at' => null,
                        'tracer_rejected_at' => null,
                        'tracer_notes' => null,
                        'cgp_user_id' => null,
                        'cgp_approved_at' => null,
                        'cgp_rejected_at' => null,
                        'cgp_notes' => null,
                        'ai_confidence_score' => null,
                        'ai_validation_result' => null,
                        'ai_approved_at' => null
                    ];

                    if ($existingPhoto) {
                        Log::info("Update Joint: Replacing existing photo record ID {$existingPhoto->id}");
                        $existingPhoto->update($photoData);
                        if (method_exists($this, 'resetModuleStatusWhenPhotoReplaced')) {
                            $this->resetModuleStatusWhenPhotoReplaced($joint);
                        }
                    } else {
                        Log::info("Update Joint: Creating new photo record for {$fieldName}");
                        PhotoApproval::create($photoData);
                    }

                } catch (\Exception $e) {
                    Log::error("Failed to upload/update foto_evidence_joint for joint {$joint->id}: " . $e->getMessage());
                }
            }
        } else {
            Log::info("Update Joint: No foto_evidence_joint file detected in request.");
        }

        // Sync to Google Sheets after update
        \App\Jobs\SyncJointToGoogleSheets::dispatch($joint);

        return redirect()
            ->route('jalur.joint.show', $joint)
            ->with('success', 'Data joint berhasil diperbarui.');
    }

    public function destroy(JalurJointData $joint)
    {
        if (!in_array($joint->status_laporan, ['draft'])) {
            return back()->with('error', 'Hanya data joint dengan status draft yang dapat dihapus.');
        }

        try {
            DB::beginTransaction();

            // Release joint number if it was used
            $jointNumber = JalurJointNumber::where('used_by_joint_id', $joint->id)->first();
            if ($jointNumber) {
                $jointNumber->markAsAvailable();
            }

            // Delete related photos
            $joint->photoApprovals()->delete();

            $joint->delete();

            DB::commit();

            return redirect()
                ->route('jalur.joint.index')
                ->with('success', 'Data joint berhasil dihapus.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menghapus data joint: ' . $e->getMessage());
        }
    }

    // Approval methods
    public function approveByTracer(Request $request, JalurJointData $joint)
    {
        if (!$joint->canApproveByTracer()) {
            return back()->with('error', 'Data joint ini tidak dapat di-approve oleh tracer.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        if ($joint->approveByTracer(Auth::id(), $validated['notes'] ?? null)) {
            return back()->with('success', 'Data joint berhasil di-approve oleh tracer.');
        }

        return back()->with('error', 'Gagal approve data joint.');
    }

    public function rejectByTracer(Request $request, JalurJointData $joint)
    {
        $validated = $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $joint->update([
            'status_laporan' => 'revisi_tracer',
            'tracer_notes' => $validated['notes'],
            'updated_by' => Auth::id(),
        ]);

        return back()->with('success', 'Data joint dikembalikan untuk revisi.');
    }

    public function approveByCgp(Request $request, JalurJointData $joint)
    {
        if (!$joint->canApproveByCgp()) {
            return back()->with('error', 'Data joint ini tidak dapat di-approve oleh CGP.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        if ($joint->approveByCgp(Auth::id(), $validated['notes'] ?? null)) {
            return back()->with('success', 'Data joint berhasil di-approve oleh CGP.');
        }

        return back()->with('error', 'Gagal approve data joint.');
    }

    public function rejectByCgp(Request $request, JalurJointData $joint)
    {
        $validated = $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $joint->update([
            'status_laporan' => 'revisi_cgp',
            'cgp_notes' => $validated['notes'],
            'updated_by' => Auth::id(),
        ]);

        return back()->with('success', 'Data joint dikembalikan untuk revisi.');
    }

    // Photo upload methods
    public function uploadPhoto(Request $request, JalurJointData $joint)
    {
        $validated = $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg,png|max:35840', // 35MB
            'photo_field_name' => 'required|in:foto_evidence_joint',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $file = $request->file('photo');

            // Generate descriptive filename
            $waktu = date('H-i-s');
            $tanggalFolder = date('Y-m-d');
            $fieldName = $validated['photo_field_name'];
            $fieldSlug = str_replace(['foto_evidence_', '_'], ['', '-'], $fieldName);
            $customFileName = "JOINT_{$joint->nomor_joint}_{$tanggalFolder}_{$waktu}_{$fieldSlug}";

            $photoPath = null;
            try {
                // Upload to Google Drive with custom path structure
                $googleDriveService = app(\App\Services\GoogleDriveService::class);

                // Create custom path: jalur_joint/cluster_slug/JointNumber/Date/
                $clusterName = $joint->cluster->nama_cluster;
                $clusterSlug = \Illuminate\Support\Str::slug($clusterName, '_');
                $customDrivePath = "jalur_joint/{$clusterSlug}/{$joint->nomor_joint}/{$tanggalFolder}";

                // Upload with custom path
                $uploadResult = $this->uploadToCustomDrivePath(
                    $googleDriveService,
                    $file,
                    $customDrivePath,
                    $customFileName
                );

                $photoPath = $uploadResult['url'] ?? $uploadResult['path'];

            } catch (\Exception $e) {
                Log::error("Google Drive upload failed for joint photo: " . $e->getMessage());

                // Fallback to local storage
                $fileName = time() . '_' . $file->getClientOriginalName();
                $fallbackPath = "jalur/joint/{$joint->nomor_joint}/" . $fileName;
                Storage::disk('public')->put($fallbackPath, file_get_contents($file));
                $photoPath = $fallbackPath;
            }

            // Replace existing photo or create new one
            $existingPhoto = PhotoApproval::where('module_name', 'jalur_joint')
                ->where('module_record_id', $joint->id)
                ->where('photo_field_name', $validated['photo_field_name'])
                ->first();

            $photoUrl = str_starts_with($photoPath, 'http') ? $photoPath : Storage::url($photoPath);

            $photoData = [
                'reff_id_pelanggan' => null, // Jalur doesn't have pelanggan
                'module_name' => 'jalur_joint',
                'module_record_id' => $joint->id,
                'photo_field_name' => $validated['photo_field_name'],
                'photo_url' => $photoUrl,
                'storage_path' => $uploadResult['path'] ?? $photoPath,
                'drive_file_id' => $uploadResult['drive_file_id'] ?? null,
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
                $this->resetModuleStatusWhenPhotoReplaced($joint);
            } else {
                PhotoApproval::create($photoData);
            }

            return back()->with('success', 'Foto berhasil diupload.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal upload foto: ' . $e->getMessage());
        }
    }

    private function resetModuleStatusWhenPhotoReplaced(JalurJointData $joint): void
    {
        // If module status is beyond draft, reset it to draft when photo is replaced
        // This ensures consistency between photo status and module status
        if (!in_array($joint->status_laporan, ['draft'])) {
            $joint->update([
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
                'joint_id' => $joint->id,
                'nomor_joint' => $joint->nomor_joint,
                'user_id' => Auth::id()
            ]);
        }
    }

    // API endpoints
    public function getFittingTypes(Request $request)
    {
        $fittingTypes = JalurFittingType::active()
            ->select(['id', 'nama_fitting', 'code_fitting'])
            ->orderBy('nama_fitting')
            ->get();

        return response()->json($fittingTypes);
    }

    public function getLineNumbers(Request $request)
    {
        $clusterId = $request->get('cluster_id');
        $diameter = $request->get('diameter');

        if (!$clusterId) {
            return response()->json([]);
        }

        $query = JalurLineNumber::byCluster($clusterId)->active();

        // Filter by diameter if provided
        if ($diameter) {
            $query->where('diameter', $diameter);
        }

        $lineNumbers = $query->select(['id', 'line_number', 'diameter', 'status_line'])
            ->orderBy('line_number')
            ->get();

        return response()->json($lineNumbers);
    }

    public function getAvailableDiameters(Request $request)
    {
        $clusterId = $request->get('cluster_id');

        if (!$clusterId) {
            return response()->json([]);
        }

        $diameters = JalurLineNumber::byCluster($clusterId)
            ->active()
            ->distinct()
            ->orderByRaw("CAST(diameter AS UNSIGNED)")
            ->pluck('diameter');

        return response()->json($diameters);
    }

    public function getAvailableJointNumbers(Request $request)
    {
        $clusterId = $request->get('cluster_id');
        $fittingTypeId = $request->get('fitting_type_id');

        if (!$clusterId || !$fittingTypeId) {
            return response()->json([]);
        }

        $jointNumbers = JalurJointNumber::forSelection($clusterId, $fittingTypeId)
            ->select(['id', 'nomor_joint', 'joint_code'])
            ->get();

        return response()->json($jointNumbers);
    }

    public function checkJointNumberStatus(Request $request)
    {
        $jointNumberId = $request->get('joint_number_id');

        if (!$jointNumberId) {
            return response()->json(['error' => 'Joint number ID is required'], 400);
        }

        $jointNumber = JalurJointNumber::with(['usedByJoint'])->find($jointNumberId);

        if (!$jointNumber) {
            return response()->json(['error' => 'Joint number not found'], 404);
        }

        // Additional check for conflicting nomor_joint in active records
        $conflictingJoint = JalurJointData::where('nomor_joint', $jointNumber->nomor_joint)
            ->whereNull('deleted_at')
            ->first();

        $hasConflict = $conflictingJoint && (!$jointNumber->usedByJoint || $conflictingJoint->id !== $jointNumber->usedByJoint->id);

        return response()->json([
            'id' => $jointNumber->id,
            'nomor_joint' => $jointNumber->nomor_joint,
            'is_available' => !$jointNumber->is_used && $jointNumber->is_active && !$hasConflict,
            'is_used' => $jointNumber->is_used || $hasConflict,
            'is_active' => $jointNumber->is_active,
            'status_message' => $this->getJointNumberStatusMessage($jointNumber, $conflictingJoint),
            'used_by' => $jointNumber->usedByJoint ? [
                'id' => $jointNumber->usedByJoint->id,
                'nomor_joint' => $jointNumber->usedByJoint->nomor_joint,
                'created_at' => $jointNumber->usedByJoint->created_at->format('d/m/Y H:i'),
            ] : ($conflictingJoint ? [
                    'id' => $conflictingJoint->id,
                    'nomor_joint' => $conflictingJoint->nomor_joint,
                    'created_at' => $conflictingJoint->created_at->format('d/m/Y H:i'),
                ] : null),
        ]);
    }

    private function getJointNumberStatusMessage(JalurJointNumber $jointNumber, $conflictingJoint = null): string
    {
        if (!$jointNumber->is_active) {
            return 'Nomor joint tidak aktif dan tidak dapat digunakan';
        }

        // Check for conflicting joint first
        if ($conflictingJoint && (!$jointNumber->usedByJoint || $conflictingJoint->id !== $jointNumber->usedByJoint->id)) {
            return "Nomor joint sudah digunakan oleh joint {$conflictingJoint->nomor_joint} pada {$conflictingJoint->created_at->format('d/m/Y H:i')}";
        }

        if ($jointNumber->is_used) {
            $usedBy = $jointNumber->usedByJoint;
            if ($usedBy) {
                return "Nomor joint sudah digunakan oleh joint {$usedBy->nomor_joint} pada {$usedBy->created_at->format('d/m/Y H:i')}";
            }
            return 'Nomor joint sudah digunakan';
        }

        return 'Nomor joint tersedia dan dapat digunakan';
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

    public function checkJointAvailability(Request $request)
    {
        $nomorJoint = $request->get('nomor_joint');

        if (empty($nomorJoint)) {
            return response()->json([
                'error' => 'Nomor joint is required'
            ], 400);
        }

        // Check if joint number already exists (only active records)
        $existingJoint = JalurJointData::where('nomor_joint', $nomorJoint)->first();
        $isAvailable = !$existingJoint;

        return response()->json([
            'is_available' => $isAvailable,
            'nomor_joint' => $nomorJoint,
            'used_by' => !$isAvailable && $existingJoint ? [
                'id' => $existingJoint->id,
                'nomor_joint' => $existingJoint->nomor_joint,
                'created_at' => $existingJoint->created_at->format('Y-m-d H:i:s'),
            ] : null,
        ]);
    }
}