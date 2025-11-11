<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\HseDailyReport;
use App\Models\HseEmergencyContact;
use App\Models\HsePhoto;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class HseDailyReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = HseDailyReport::with(['creator', 'pekerjaanHarian', 'tenagaKerja'])
            ->orderBy('tanggal_laporan', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('tanggal_laporan', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('tanggal_laporan', '<=', $request->end_date);
        }

        // Search by project name
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('nama_proyek', 'like', '%' . $request->search . '%')
                  ->orWhere('catatan', 'like', '%' . $request->search . '%');
            });
        }

        $reports = $query->paginate(15);

        // Statistics
        $stats = [
            'total_reports' => HseDailyReport::count(),
            'draft' => HseDailyReport::draft()->count(),
            'submitted' => HseDailyReport::submitted()->count(),
            'approved' => HseDailyReport::approved()->count(),
            'total_jka' => HseDailyReport::latest('tanggal_laporan')->first()->jka_kumulatif ?? 0,
        ];

        return view('hse.index', compact('reports', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Get last JKA kumulatif
        $lastReport = HseDailyReport::orderBy('tanggal_laporan', 'desc')->first();
        $lastJkaKumulatif = $lastReport ? $lastReport->jka_kumulatif : 0;

        // Get emergency contacts
        $emergencyContacts = HseEmergencyContact::active()->ordered()->get();

        // Default values
        $defaults = [
            'nama_proyek' => 'Pembangunan Jargas Gaskita Di Kabupaten Sleman',
            'pemberi_pekerjaan' => 'PGN - CGP',
            'kontraktor' => 'PT. KIAN SANTANG MULIATAMA TBK',
            'sub_kontraktor' => '',
            'jka_kumulatif' => $lastJkaKumulatif,
        ];

        // Role templates (27 roles)
        $roleTemplates = [
            'KSM' => [
                'PM' => 'PM',
                'CM/SM' => 'CM/SM',
                'HSE' => 'HSE',
                'SPV' => 'SPV',
                'Project Control' => 'Project Control',
                'Warehouse, Log, Material Control' => 'Warehouse, Log, Material Control',
                'Admin, Doc Control' => 'Admin, Doc Control',
                'Mandor' => 'Mandor',
                'Drafter' => 'Drafter',
                'Traceability' => 'Traceability',
                'Permit/Humas' => 'Permit/Humas',
                'Tim Validasi' => 'Tim Validasi',
                'Tim Sambungan Kompor (SK)' => 'Tim Sambungan Kompor (SK)',
                'Tim Sambungan Rumah (SR)' => 'Tim Sambungan Rumah (SR)',
                'Tim Galian/Jalur 63' => 'Tim Galian/Jalur 63',
                'Tim Galian/Jalur 180' => 'Tim Galian/Jalur 180',
                'Tim Operator dan Helper' => 'Tim Operator dan Helper',
                'Tim Perapihan & Reinstatement' => 'Tim Perapihan & Reinstatement',
                'Tim Pemasangan MGRT' => 'Tim Pemasangan MGRT',
                'Tim Konversi Kompor' => 'Tim Konversi Kompor',
                'Tim Pneumatic/Flushing' => 'Tim Pneumatic/Flushing',
                'Tim Sipil dan Pemasangan Patok/Marker Post' => 'Tim Sipil dan Pemasangan Patok/Marker Post',
                'Tim Fabrikasi Patok' => 'Tim Fabrikasi Patok',
                'Driver, OB, dan Security' => 'Driver, OB, dan Security',
                'Tim Konstruksi Sipil RS' => 'Tim Konstruksi Sipil RS',
                'Tim Pelanggan Komersil PK' => 'Tim Pelanggan Komersil PK',
            ],
            'PGN-CGP' => [
                'HSE' => 'HSE',
                'QC' => 'QC',
                'Waspang' => 'Waspang',
            ],
            'OMM' => [
                'Area Semarang' => 'Area Semarang',
                'Sales' => 'Sales',
            ]
        ];

        // Program HSE default
        $programHseDefaults = [
            ['nama_program' => 'Pola hidup bersih dan sehat'],
            ['nama_program' => 'TBM'],
            ['nama_program' => 'House Keeping'],
            ['nama_program' => 'Safety Driving'],
            ['nama_program' => 'Fit to work'],
        ];

        // TBM Materi default
        $tbmMateriDefaults = [
            ['materi_pembahasan' => 'Menjelaskan pekerjaan pada Siang hari dan sesuai dengan potensi bahayanya.', 'urutan' => 1],
            ['materi_pembahasan' => 'Safety Driving Saat berkendara di jalan raya dan jalan area perumahan.', 'urutan' => 2],
            ['materi_pembahasan' => 'Edukasi penyimpanan peralatan di mobil.', 'urutan' => 3],
            ['materi_pembahasan' => 'Mengingatkan pentingnya APD saat melakukan pekerjaan.', 'urutan' => 4],
            ['materi_pembahasan' => 'Mengingatkan PENTINGNYA kesehatan pekerja / Fit to Work dan keselamatan bekerja.', 'urutan' => 5],
            ['materi_pembahasan' => 'Berdo\'a sebelum melakukan yang pekerjaan.', 'urutan' => 6],
            ['materi_pembahasan' => 'Penjelasan JSA setiap pekerjaan ke pekerja.', 'urutan' => 7],
        ];

        $defaults['program_hse'] = $programHseDefaults;
        $defaults['tbm_materi'] = $tbmMateriDefaults;

        return view('hse.create', compact(
            'defaults',
            'emergencyContacts',
            'roleTemplates',
            'lastJkaKumulatif'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal_laporan' => ['required', 'date'],
            'nama_proyek' => ['required', 'string', 'max:255'],
            'pemberi_pekerjaan' => ['required', 'string', 'max:255'],
            'kontraktor' => ['required', 'string', 'max:255'],
            'sub_kontraktor' => ['nullable', 'string', 'max:255'],
            'cuaca' => ['required', 'in:cerah,berawan,mendung,hujan,hujan_lebat'],
            'catatan' => ['nullable', 'string'],

            // Pekerjaan Harian
            'pekerjaan' => ['required', 'array', 'min:1'],
            'pekerjaan.*.jenis_pekerjaan' => ['required', 'string'],
            'pekerjaan.*.deskripsi_pekerjaan' => ['required', 'string'],
            'pekerjaan.*.lokasi_detail' => ['required', 'string'],
            'pekerjaan.*.google_maps_link' => ['nullable', 'url'],

            // Tenaga Kerja
            'tenaga_kerja' => ['required', 'array', 'min:1'],
            'tenaga_kerja.*.kategori_team' => ['required', 'in:PGN-CGP,OMM,KSM'],
            'tenaga_kerja.*.role_name' => ['required', 'string'],
            'tenaga_kerja.*.jumlah_orang' => ['required', 'integer', 'min:1'],

            // TBM
            'tbm_waktu' => ['required', 'date_format:H:i'],
            'tbm_jumlah_peserta' => ['required', 'integer', 'min:0'],
            'tbm_materi' => ['required', 'array', 'min:1'],
            'tbm_materi.*.materi_pembahasan' => ['required', 'string'],
            'tbm_materi.*.urutan' => ['required', 'integer'],

            // Program HSE
            'program_hse' => ['nullable', 'array'],
            'program_hse.*.nama_program' => ['required', 'string'],

            // Photos (optional)
            'photos' => ['nullable', 'array'],
            'photos.*.file' => ['required', 'string'], // base64
            'photos.*.category' => ['required', 'in:pekerjaan,tbm,kondisi_site,apd,housekeeping,incident'],
            'photos.*.keterangan' => ['nullable', 'string', 'max:500'],
            'photos.*.filename' => ['required', 'string'],
        ]);

        // Get last JKA kumulatif
        $lastJkaKumulatif = HseDailyReport::orderBy('tanggal_laporan', 'desc')
            ->value('jka_kumulatif') ?? 0;

        DB::beginTransaction();
        try {
            // Create daily report
            $report = HseDailyReport::create([
                'tanggal_laporan' => $validated['tanggal_laporan'],
                'nama_proyek' => $validated['nama_proyek'],
                'pemberi_pekerjaan' => $validated['pemberi_pekerjaan'],
                'kontraktor' => $validated['kontraktor'],
                'sub_kontraktor' => $validated['sub_kontraktor'] ?? null,
                'cuaca' => $validated['cuaca'],
                'jka_kumulatif' => 0, // Will be updated after calculating JKA hari ini
                'catatan' => $validated['catatan'] ?? null,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            // Create pekerjaan harian
            foreach ($validated['pekerjaan'] as $pekerjaan) {
                $report->pekerjaanHarian()->create($pekerjaan);
            }

            // Create tenaga kerja
            foreach ($validated['tenaga_kerja'] as $tk) {
                $report->tenagaKerja()->create($tk);
            }

            // Update totals (calculates total_pekerja and jka_hari_ini)
            $report->updateTotals();

            // Refresh model to get updated jka_hari_ini
            $report->refresh();

            // Update JKA kumulatif = last kumulatif + current hari ini
            $report->update([
                'jka_kumulatif' => $lastJkaKumulatif + $report->jka_hari_ini,
            ]);

            // Create TBM
            $tbm = $report->toolboxMeeting()->create([
                'tanggal_tbm' => $validated['tanggal_laporan'],
                'waktu_mulai' => $validated['tbm_waktu'],
                'waktu_selesai' => null,
                'lokasi' => null,
                'jumlah_peserta' => $validated['tbm_jumlah_peserta'],
                'catatan' => null,
                'created_by' => auth()->id(),
            ]);

            // Create TBM Materi
            foreach ($validated['tbm_materi'] as $materi) {
                $tbm->materiList()->create([
                    'urutan' => $materi['urutan'],
                    'materi_pembahasan' => $materi['materi_pembahasan'],
                ]);
            }

            // Create Program HSE
            if (!empty($validated['program_hse'])) {
                foreach ($validated['program_hse'] as $program) {
                    $report->programHarian()->create($program);
                }
            }

            // Upload Photos if any
            if (!empty($validated['photos'])) {
                $driveService = app(GoogleDriveService::class);

                if ($driveService->isAvailable()) {
                    foreach ($validated['photos'] as $photoData) {
                        try {
                            // Decode base64
                            $base64Data = $photoData['file'];
                            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                                $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
                                $type = strtolower($type[1]); // jpg, png, gif
                                $imageData = base64_decode($base64Data);

                                // Create temp file
                                $tempFile = tmpfile();
                                $tempPath = stream_get_meta_data($tempFile)['uri'];
                                fwrite($tempFile, $imageData);

                                // Build folder path with full date (YYYY-MM-DD)
                                $dateFolder = Carbon::parse($report->tanggal_laporan)->format('Y-m-d');
                                $folderPath = "HSE_Data/{$dateFolder}/{$photoData['category']}";
                                $folderId = $driveService->ensureNestedFolders($folderPath);

                                // Upload to Google Drive
                                $uploadedFile = new \Illuminate\Http\UploadedFile(
                                    $tempPath,
                                    $photoData['filename'],
                                    mime_content_type($tempPath),
                                    null,
                                    true
                                );

                                $result = $driveService->uploadFile($uploadedFile, $folderId, $photoData['filename']);

                                // Save to database
                                $report->photos()->create([
                                    'photo_category' => $photoData['category'],
                                    'photo_url' => $result['webViewLink'],
                                    'drive_file_id' => $result['id'],
                                    'drive_link' => $result['webViewLink'],
                                    'storage_path' => $folderPath . '/' . $photoData['filename'],
                                    'storage_disk' => 'gdrive',
                                    'keterangan' => $photoData['keterangan'] ?? null,
                                    'uploaded_by' => auth()->id(),
                                ]);

                                fclose($tempFile);

                                Log::info('HSE photo uploaded during create', [
                                    'report_id' => $report->id,
                                    'category' => $photoData['category'],
                                    'filename' => $photoData['filename'],
                                ]);
                            }
                        } catch (\Exception $e) {
                            Log::error('Failed to upload photo during create', [
                                'report_id' => $report->id,
                                'error' => $e->getMessage(),
                            ]);
                            // Continue with other photos
                        }
                    }
                }
            }

            DB::commit();

            $message = 'Laporan HSE berhasil dibuat!';
            if (!empty($validated['photos'])) {
                $photoCount = count($validated['photos']);
                $message .= " {$photoCount} foto dokumentasi berhasil diupload.";
            }

            return redirect()
                ->route('hse.daily-reports.show', $report->id)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create HSE report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Gagal membuat laporan: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $report = HseDailyReport::with([
            'pekerjaanHarian',
            'tenagaKerja',
            'toolboxMeeting.materiList',
            'programHarian',
            'photos',
            'creator',
            'approver'
        ])->findOrFail($id);

        // Get emergency contacts for display
        $emergencyContacts = HseEmergencyContact::active()->ordered()->get();

        return view('hse.show', compact('report', 'emergencyContacts'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $report = HseDailyReport::with([
            'pekerjaanHarian',
            'tenagaKerja',
            'toolboxMeeting.materiList',
            'programHarian',
        ])->findOrFail($id);

        // Check if can edit
        if (!$report->canEdit()) {
            return redirect()
                ->route('hse.daily-reports.show', $report->id)
                ->withErrors(['error' => 'Laporan tidak dapat diedit karena sudah ' . $report->getStatusLabel()]);
        }

        // Get emergency contacts
        $emergencyContacts = HseEmergencyContact::active()->ordered()->get();

        // Default values
        $defaults = [
            'nama_proyek' => 'Pembangunan Jargas Gaskita Di Kabupaten Sleman',
            'pemberi_pekerjaan' => 'PGN - CGP',
            'kontraktor' => 'PT. KIAN SANTANG MULIATAMA TBK',
            'sub_kontraktor' => '',
        ];

        // Role templates
        $roleTemplates = [
            'KSM' => [
                'PM', 'CM/SM', 'HSE', 'SPV', 'Project Control',
                'Warehouse, Log, Material Control', 'Admin, Doc Control',
                'Mandor', 'Drafter', 'Traceability', 'Permit/Humas',
                'Tim Validasi', 'Tim Sambungan Kompor (SK)', 'Tim Sambungan Rumah (SR)',
                'Tim Galian/Jalur 63', 'Tim Galian/Jalur 180', 'Tim Operator dan Helper',
                'Tim Perapihan & Reinstatement', 'Tim Pemasangan MGRT', 'Tim Konversi Kompor',
                'Tim Pneumatic/Flushing', 'Tim Sipil dan Pemasangan Patok/Marker Post',
                'Tim Fabrikasi Patok', 'Driver, OB, dan Security',
                'Tim Konstruksi Sipil RS', 'Tim Pelanggan Komersil PK',
            ],
            'PGN-CGP' => ['HSE', 'QC', 'Waspang'],
            'OMM' => ['Area Semarang', 'Sales'],
        ];

        // Program HSE default
        $programHseDefaults = [
            ['nama_program' => 'Pola hidup bersih dan sehat'],
            ['nama_program' => 'TBM'],
            ['nama_program' => 'House Keeping'],
            ['nama_program' => 'Safety Driving'],
            ['nama_program' => 'Fit to work'],
        ];

        // TBM Materi default
        $tbmMateriDefaults = [
            ['materi_pembahasan' => 'Pengenalan bahaya di area kerja', 'urutan' => 1],
            ['materi_pembahasan' => 'Penggunaan APD yang benar', 'urutan' => 2],
            ['materi_pembahasan' => 'Prosedur keselamatan kerja', 'urutan' => 3],
            ['materi_pembahasan' => 'Pertolongan pertama pada kecelakaan (P3K)', 'urutan' => 4],
            ['materi_pembahasan' => 'Identifikasi risiko pekerjaan hari ini', 'urutan' => 5],
            ['materi_pembahasan' => 'Komunikasi dan koordinasi tim', 'urutan' => 6],
            ['materi_pembahasan' => 'Emergency response procedure', 'urutan' => 7],
        ];

        $defaults['program_hse'] = $programHseDefaults;
        $defaults['tbm_materi'] = $tbmMateriDefaults;

        return view('hse.edit', compact('report', 'emergencyContacts', 'roleTemplates', 'defaults'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $report = HseDailyReport::findOrFail($id);

        if (!$report->canEdit()) {
            return back()->withErrors(['error' => 'Laporan tidak dapat diedit']);
        }

        $validated = $request->validate([
            'tanggal_laporan' => ['required', 'date'],
            'nama_proyek' => ['required', 'string', 'max:255'],
            'pemberi_pekerjaan' => ['required', 'string', 'max:255'],
            'kontraktor' => ['required', 'string', 'max:255'],
            'sub_kontraktor' => ['nullable', 'string', 'max:255'],
            'cuaca' => ['required', 'in:cerah,berawan,mendung,hujan,hujan_lebat'],
            'catatan' => ['nullable', 'string'],

            // Pekerjaan Harian
            'pekerjaan' => ['required', 'array', 'min:1'],
            'pekerjaan.*.jenis_pekerjaan' => ['required', 'string'],
            'pekerjaan.*.deskripsi_pekerjaan' => ['required', 'string'],
            'pekerjaan.*.lokasi_detail' => ['required', 'string'],
            'pekerjaan.*.google_maps_link' => ['nullable', 'url'],

            // Tenaga Kerja
            'tenaga_kerja' => ['required', 'array', 'min:1'],
            'tenaga_kerja.*.kategori_team' => ['required', 'in:PGN-CGP,OMM,KSM'],
            'tenaga_kerja.*.role_name' => ['required', 'string'],
            'tenaga_kerja.*.jumlah_orang' => ['required', 'integer', 'min:1'],

            // TBM
            'tbm_waktu' => ['required', 'date_format:H:i'],
            'tbm_jumlah_peserta' => ['required', 'integer', 'min:0'],
            'tbm_materi' => ['required', 'array', 'min:1'],
            'tbm_materi.*.materi_pembahasan' => ['required', 'string'],
            'tbm_materi.*.urutan' => ['required', 'integer'],

            // Program HSE
            'program_hse' => ['nullable', 'array'],
            'program_hse.*.nama_program' => ['required', 'string'],

            // Photos
            'delete_photos' => ['nullable', 'array'],
            'delete_photos.*' => ['integer', 'exists:hse_photos,id'],
            'photos' => ['nullable', 'array'],
            'photos.*.file' => ['required', 'string'], // base64
            'photos.*.category' => ['required', 'in:pekerjaan,tbm,kondisi_site,apd,housekeeping,incident'],
            'photos.*.keterangan' => ['nullable', 'string', 'max:500'],
            'photos.*.filename' => ['required', 'string'],
        ]);

        DB::beginTransaction();
        try {
            // Update report
            $report->update([
                'tanggal_laporan' => $validated['tanggal_laporan'],
                'nama_proyek' => $validated['nama_proyek'],
                'pemberi_pekerjaan' => $validated['pemberi_pekerjaan'],
                'kontraktor' => $validated['kontraktor'],
                'sub_kontraktor' => $validated['sub_kontraktor'] ?? null,
                'cuaca' => $validated['cuaca'],
                'catatan' => $validated['catatan'] ?? null,
            ]);

            // Delete and recreate pekerjaan
            $report->pekerjaanHarian()->delete();
            foreach ($validated['pekerjaan'] as $pekerjaan) {
                $report->pekerjaanHarian()->create($pekerjaan);
            }

            // Delete and recreate tenaga kerja
            $report->tenagaKerja()->delete();
            foreach ($validated['tenaga_kerja'] as $tk) {
                $report->tenagaKerja()->create($tk);
            }

            // Update totals
            $report->updateTotals();

            // Update TBM
            if ($report->toolboxMeeting) {
                // Update TBM waktu dan jumlah peserta
                $report->toolboxMeeting->update([
                    'waktu_mulai' => $validated['tbm_waktu'],
                    'jumlah_peserta' => $validated['tbm_jumlah_peserta'],
                ]);

                // Update TBM materi
                $report->toolboxMeeting->materiList()->delete();
                foreach ($validated['tbm_materi'] as $materi) {
                    $report->toolboxMeeting->materiList()->create([
                        'urutan' => $materi['urutan'],
                        'materi_pembahasan' => $materi['materi_pembahasan'],
                    ]);
                }
            }

            // Update Program HSE
            $report->programHarian()->delete();
            if (!empty($validated['program_hse'])) {
                foreach ($validated['program_hse'] as $program) {
                    $report->programHarian()->create($program);
                }
            }

            // Handle Photo Deletions
            if (!empty($validated['delete_photos'])) {
                $driveService = app(GoogleDriveService::class);

                foreach ($validated['delete_photos'] as $photoId) {
                    $photo = HsePhoto::where('id', $photoId)
                        ->where('daily_report_id', $report->id)
                        ->first();

                    if ($photo) {
                        // Delete from Google Drive
                        if ($photo->drive_file_id && $driveService->isAvailable()) {
                            try {
                                $driveService->deleteFile($photo->drive_file_id);
                            } catch (\Exception $e) {
                                Log::warning('Failed to delete photo from Drive', [
                                    'photo_id' => $photoId,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }

                        $photo->delete();
                        Log::info('HSE photo deleted during edit', ['photo_id' => $photoId]);
                    }
                }
            }

            // Upload New Photos
            if (!empty($validated['photos'])) {
                $driveService = app(GoogleDriveService::class);

                if ($driveService->isAvailable()) {
                    foreach ($validated['photos'] as $photoData) {
                        try {
                            // Decode base64
                            $base64Data = $photoData['file'];
                            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                                $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
                                $type = strtolower($type[1]); // jpg, png, gif
                                $imageData = base64_decode($base64Data);

                                // Create temp file
                                $tempFile = tmpfile();
                                $tempPath = stream_get_meta_data($tempFile)['uri'];
                                fwrite($tempFile, $imageData);

                                // Build folder path with full date (YYYY-MM-DD)
                                $dateFolder = Carbon::parse($report->tanggal_laporan)->format('Y-m-d');
                                $folderPath = "HSE_Data/{$dateFolder}/{$photoData['category']}";
                                $folderId = $driveService->ensureNestedFolders($folderPath);

                                // Upload to Google Drive
                                $uploadedFile = new \Illuminate\Http\UploadedFile(
                                    $tempPath,
                                    $photoData['filename'],
                                    mime_content_type($tempPath),
                                    null,
                                    true
                                );

                                $result = $driveService->uploadFile($uploadedFile, $folderId, $photoData['filename']);

                                // Save to database
                                $report->photos()->create([
                                    'photo_category' => $photoData['category'],
                                    'photo_url' => $result['webViewLink'],
                                    'drive_file_id' => $result['id'],
                                    'drive_link' => $result['webViewLink'],
                                    'storage_path' => $folderPath . '/' . $photoData['filename'],
                                    'storage_disk' => 'gdrive',
                                    'keterangan' => $photoData['keterangan'] ?? null,
                                    'uploaded_by' => auth()->id(),
                                ]);

                                fclose($tempFile);

                                Log::info('HSE photo uploaded during edit', [
                                    'report_id' => $report->id,
                                    'category' => $photoData['category'],
                                    'filename' => $photoData['filename'],
                                ]);
                            }
                        } catch (\Exception $e) {
                            Log::error('Failed to upload photo during edit', [
                                'report_id' => $report->id,
                                'error' => $e->getMessage(),
                            ]);
                            // Continue with other photos
                        }
                    }
                }
            }

            DB::commit();

            $message = 'Laporan HSE berhasil diupdate!';
            $deleted = !empty($validated['delete_photos']) ? count($validated['delete_photos']) : 0;
            $uploaded = !empty($validated['photos']) ? count($validated['photos']) : 0;

            if ($deleted > 0 || $uploaded > 0) {
                $photoMsg = [];
                if ($deleted > 0) $photoMsg[] = "{$deleted} foto dihapus";
                if ($uploaded > 0) $photoMsg[] = "{$uploaded} foto baru diupload";
                $message .= " " . implode(", ", $photoMsg) . ".";
            }

            return redirect()
                ->route('hse.daily-reports.show', $report->id)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update HSE report', [
                'report_id' => $id,
                'error' => $e->getMessage()
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Gagal update laporan: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $report = HseDailyReport::findOrFail($id);

        if (!$report->canEdit()) {
            return back()->withErrors(['error' => 'Laporan tidak dapat dihapus']);
        }

        try {
            // Delete photos from storage
            foreach ($report->photos as $photo) {
                if ($photo->storage_disk === 'local' && $photo->storage_path) {
                    Storage::disk('local')->delete($photo->storage_path);
                }
            }

            $report->delete();

            return redirect()
                ->route('hse.index')
                ->with('success', 'Laporan HSE berhasil dihapus');

        } catch (\Exception $e) {
            Log::error('Failed to delete HSE report', [
                'report_id' => $id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Gagal menghapus laporan']);
        }
    }

    /**
     * Submit report for approval
     */
    public function submit(string $id)
    {
        $report = HseDailyReport::findOrFail($id);

        if (!$report->canSubmit()) {
            return back()->withErrors(['error' => 'Laporan belum lengkap untuk disubmit']);
        }

        $report->submit();

        return redirect()
            ->route('hse.daily-reports.show', $report->id)
            ->with('success', 'Laporan berhasil disubmit untuk approval');
    }

    /**
     * Approve report
     */
    public function approve(string $id)
    {
        $report = HseDailyReport::findOrFail($id);

        if (!$report->canApprove()) {
            return back()->withErrors(['error' => 'Laporan tidak dapat diapprove']);
        }

        $report->approve(auth()->id());

        return redirect()
            ->route('hse.daily-reports.show', $report->id)
            ->with('success', 'Laporan berhasil diapprove');
    }

    /**
     * Reject report
     */
    public function reject(string $id)
    {
        $report = HseDailyReport::findOrFail($id);

        if ($report->status !== 'submitted') {
            return back()->withErrors(['error' => 'Laporan tidak dapat direject']);
        }

        $report->reject();

        return redirect()
            ->route('hse.daily-reports.show', $report->id)
            ->with('success', 'Laporan direject, silakan perbaiki');
    }

    /**
     * Upload photos to Google Drive
     */
    public function uploadPhoto(Request $request, string $id)
    {
        $report = HseDailyReport::findOrFail($id);

        if (!$report->canEdit()) {
            return back()->withErrors(['error' => 'Laporan tidak dapat diedit']);
        }

        $request->validate([
            'photo' => ['required', 'image', 'max:10240'], // 10MB
            'category' => ['required', 'in:pekerjaan,tbm,kondisi_site,apd,housekeeping,incident'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $driveService = app(GoogleDriveService::class);

            if (!$driveService->isAvailable()) {
                throw new \Exception('Google Drive service tidak tersedia');
            }

            // Build folder path: HSE_Data/{report_id}/{category}
            $folderPath = "HSE_Data/{$report->id}/{$request->category}";
            $folderId = $driveService->ensureNestedFolders($folderPath);

            // Generate unique filename
            $extension = $request->file('photo')->getClientOriginalExtension();
            $filename = time() . '_' . uniqid() . '.' . $extension;

            // Upload to Google Drive
            $result = $driveService->uploadFile($request->file('photo'), $folderId, $filename);

            // Save to database
            $photo = $report->photos()->create([
                'photo_category' => $request->category,
                'photo_url' => $result['webViewLink'],
                'drive_file_id' => $result['id'],
                'drive_link' => $result['webViewLink'],
                'storage_path' => $folderPath . '/' . $filename,
                'storage_disk' => 'gdrive',
                'keterangan' => $request->keterangan,
                'uploaded_by' => auth()->id(),
            ]);

            Log::info('HSE photo uploaded successfully', [
                'report_id' => $report->id,
                'photo_id' => $photo->id,
                'category' => $request->category,
                'drive_file_id' => $result['id'],
            ]);

            return back()->with('success', 'Foto berhasil diupload');

        } catch (\Exception $e) {
            Log::error('Failed to upload HSE photo', [
                'report_id' => $id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Gagal upload foto: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete photo from Google Drive
     */
    public function deletePhoto(string $id, string $photoId)
    {
        $report = HseDailyReport::findOrFail($id);

        if (!$report->canEdit()) {
            return back()->withErrors(['error' => 'Laporan tidak dapat diedit']);
        }

        try {
            $photo = HsePhoto::where('daily_report_id', $id)
                ->where('id', $photoId)
                ->firstOrFail();

            // Delete from Google Drive
            if ($photo->drive_file_id) {
                $driveService = app(GoogleDriveService::class);
                if ($driveService->isAvailable()) {
                    $driveService->deleteFile($photo->drive_file_id);
                }
            }

            // Delete from local storage (fallback)
            if ($photo->storage_disk === 'local' && $photo->storage_path) {
                Storage::disk('local')->delete($photo->storage_path);
            }

            $photo->delete();

            Log::info('HSE photo deleted successfully', [
                'report_id' => $id,
                'photo_id' => $photoId,
            ]);

            return back()->with('success', 'Foto berhasil dihapus');

        } catch (\Exception $e) {
            Log::error('Failed to delete HSE photo', [
                'report_id' => $id,
                'photo_id' => $photoId,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Gagal menghapus foto']);
        }
    }

    /**
     * Export daily report to PDF
     */
    public function exportDailyPdf(string $id)
    {
        // Increase memory limit for PDF generation with photos
        ini_set('memory_limit', '512M');
        set_time_limit(300); // 5 minutes

        $report = HseDailyReport::with([
            'pekerjaanHarian',
            'tenagaKerja',
            'toolboxMeeting.materiList',
            'programHarian',
            'photos.uploader',
            'creator',
            'approver',
        ])->findOrFail($id);

        // Get active emergency contacts (global, not specific to this report)
        $emergencyContacts = HseEmergencyContact::active()
            ->ordered()
            ->get();

        $pdf = Pdf::loadView('hse.pdf.daily', compact('report', 'emergencyContacts'))
            ->setPaper('a4', 'portrait')
            ->setOption('enable-local-file-access', true);

        $filename = 'Laporan_HSE_' . Carbon::parse($report->tanggal_laporan)->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export weekly report to PDF
     */
    public function exportWeeklyPdf(Request $request)
    {
        // Increase memory limit for PDF with photos
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $reports = HseDailyReport::with([
            'pekerjaanHarian',
            'tenagaKerja',
            'toolboxMeeting.materiList',
            'programHarian',
            'photos.uploader',
        ])
            ->whereBetween('tanggal_laporan', [$request->start_date, $request->end_date])
            ->orderBy('tanggal_laporan', 'asc')
            ->get();

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $pdf = Pdf::loadView('hse.pdf.weekly', compact('reports', 'startDate', 'endDate'))
            ->setPaper('a4', 'portrait')
            ->setOption('enable-local-file-access', true);

        $filename = 'Laporan_HSE_Mingguan_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export monthly report to PDF
     */
    public function exportMonthlyPdf(Request $request)
    {
        // Increase memory limit for PDF with photos
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $reports = HseDailyReport::with([
            'pekerjaanHarian',
            'tenagaKerja',
            'toolboxMeeting.materiList',
            'programHarian',
            'photos.uploader',
        ])
            ->whereYear('tanggal_laporan', $request->year)
            ->whereMonth('tanggal_laporan', $request->month)
            ->orderBy('tanggal_laporan', 'asc')
            ->get();

        $date = Carbon::createFromDate($request->year, $request->month, 1);

        // Calculate statistics
        $stats = [
            'total_reports' => $reports->count(),
            'total_workers' => $reports->sum('total_pekerja'),
            'total_jka' => $reports->sum('jka_hari_ini'),
            'avg_workers_per_day' => $reports->count() > 0 ? round($reports->sum('total_pekerja') / $reports->count(), 2) : 0,
        ];

        $pdf = Pdf::loadView('hse.pdf.monthly', compact('reports', 'date', 'stats'))
            ->setPaper('a4', 'landscape')
            ->setOption('enable-local-file-access', true);

        $filename = 'Laporan_HSE_Bulanan_' . $date->format('Y-m') . '.pdf';

        return $pdf->download($filename);
    }

}
