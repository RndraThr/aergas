<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\OpenAIService;
use App\Models\GasInData;
use App\Services\PhotoApprovalService;
use App\Services\BeritaAcaraService;
use App\Helpers\ReffIdHelper;

class GasInDataController extends Controller
{
   public function __construct(
       private ?PhotoApprovalService $photoSvc = null,
   ) {}

   public function index(Request $r)
   {
       $q = GasInData::with(['calonPelanggan', 'srData:reff_id_pelanggan,no_seri_mgrt,merk_brand_mgrt'])
           ->withCount([
               'photoApprovals as rejected_photos_count' => function($query) {
                   $query->where(function($q) {
                       $q->whereNotNull('tracer_rejected_at')
                         ->orWhereNotNull('cgp_rejected_at');
                   });
               }
           ])
           ->latest('id');

       if ($r->filled('q')) {
           $term = trim((string) $r->get('q'));
           $q->where(function($w) use ($term) {
               $w->where('reff_id_pelanggan','like',"%{$term}%")
                 ->orWhere('status','like',"%{$term}%")
                 ->orWhere('module_status','like',"%{$term}%");
           });
       }

       if ($r->filled('module_status')) {
           $q->where('module_status', $r->get('module_status'));
       }

       if ($r->filled('tanggal_dari')) {
           $q->whereDate('tanggal_gas_in', '>=', $r->get('tanggal_dari'));
       }

       if ($r->filled('tanggal_sampai')) {
           $q->whereDate('tanggal_gas_in', '<=', $r->get('tanggal_sampai'));
       }

       $gasIn = $q->paginate((int) $r->get('per_page', 15))->withQueryString();

       // Load related data for each Gas In
       $gasIn->load('createdBy:id,name', 'photoApprovals');

       // Add photo status details for each Gas In
       $gasIn->getCollection()->transform(function ($item) {
           $item->photo_status_details = $this->getPhotoStatusDetails($item);
           return $item;
       });

       if ($r->wantsJson() || $r->ajax()) {
           // Calculate stats
           $allGasIn = GasInData::all();
           $stats = [
               'total' => $gasIn->total(),
               'draft' => $allGasIn->where('module_status', 'draft')->count(),
               'ready' => $allGasIn->where('module_status', 'tracer_review')->count(),
               'completed' => $allGasIn->where('module_status', 'completed')->count(),
           ];

           return response()->json([
               'success' => true,
               'data' => $gasIn,
               'stats' => $stats
           ]);
       }

       return view('gas-in.index', compact('gasIn'));
   }

   public function create()
   {
       $photoDefs = $this->buildPhotoDefs('GAS_IN');
       return view('gas-in.create', compact('photoDefs'));
   }

   public function edit(GasInData $gasIn)
   {
       $gasIn->load(['calonPelanggan','photoApprovals','files']);
       $photoDefs = $this->buildPhotoDefs('GAS_IN');
       return view('gas-in.edit', compact('gasIn','photoDefs'));
   }

   public function show(Request $r, GasInData $gasIn)
   {
       $gasIn->load(['calonPelanggan', 'photoApprovals.tracerUser', 'photoApprovals.cgpUser', 'files', 'tracerApprovedBy', 'cgpApprovedBy']);

       if ($r->wantsJson() || $r->ajax()) {
           return response()->json($gasIn);
       }

       return view('gas-in.show', compact('gasIn'));
   }

   public function store(Request $r)
   {
       // Normalize reff_id_pelanggan (uppercase + auto-pad to 8 digits if numeric)
       $r->merge([
           'reff_id_pelanggan' => ReffIdHelper::normalize($r->input('reff_id_pelanggan')),
       ]);

       // Check if customer status is batal
       $customer = \App\Models\CalonPelanggan::where('reff_id_pelanggan', $r->reff_id_pelanggan)->first();
       if ($customer && $customer->status === 'batal') {
           return response()->json([
               'success' => false,
               'message' => 'Tidak dapat membuat Gas In untuk customer dengan status batal. Customer ini sudah dibatalkan.'
           ], 422);
       }

       $v = Validator::make($r->all(), [
           'reff_id_pelanggan' => [
               'required',
               'string',
               'max:50',
               Rule::exists('calon_pelanggan','reff_id_pelanggan'),
               Rule::unique('gas_in_data','reff_id_pelanggan')->whereNull('deleted_at')
           ],
           'tanggal_gas_in' => ['required','date'],
           'notes' => ['nullable','string'],
       ], [
           'reff_id_pelanggan.unique' => 'Gas In untuk reff_id ini sudah ada. Tidak boleh membuat Gas In duplikat.'
       ]);

       if ($v->fails()) {
           return response()->json(['success'=>false,'errors'=>$v->errors()], 422);
       }

       // Double check to prevent race condition (exclude soft deleted records)
       $existingGasIn = GasInData::where('reff_id_pelanggan', $r->reff_id_pelanggan)
                                ->whereNull('deleted_at')
                                ->first();
       if ($existingGasIn) {
           return response()->json([
               'success' => false,
               'message' => 'Gas In untuk reff_id ' . $r->reff_id_pelanggan . ' sudah ada.',
               'existing_id' => $existingGasIn->id,
               'existing_status' => $existingGasIn->status
           ], 422);
       }

       $data = $v->validated();
       $data['status'] = GasInData::STATUS_DRAFT;
       $data['created_by'] = Auth::id();
       $data['updated_by'] = Auth::id();

       $gasIn = GasInData::create($data);
       $this->audit('create', $gasIn, [], $gasIn->toArray());

       return response()->json(['success'=>true,'data'=>$gasIn], 201);
   }

   public function update(Request $r, GasInData $gasIn)
   {
       if (!$gasIn->canEdit()) {
           return response()->json([
               'success'=>false,
               'message'=>'Tidak diizinkan untuk mengedit data ini. Status: ' . ($gasIn->module_status ?? $gasIn->status)
           ], 422);
       }

       $v = Validator::make($r->all(), [
           'tanggal_gas_in' => ['required','date'],
           'notes' => ['nullable','string'],
           'created_by' => ['nullable','integer','exists:users,id'],
       ]);

       if ($v->fails()) {
           return response()->json(['success'=>false,'errors'=>$v->errors()], 422);
       }

       $old = $gasIn->getOriginal();
       $gasIn->fill($v->validated());
       $gasIn->updated_by = Auth::id();
       $gasIn->save();

       $this->audit('update', $gasIn, $old, $gasIn->toArray());
       return response()->json(['success'=>true,'data'=>$gasIn]);
   }

   public function destroy(GasInData $gasIn)
   {
       $old = $gasIn->toArray();

       try {
           // Get all related photos/files
           $photoApprovals = $gasIn->photoApprovals;

           // Track folder paths for deletion
           $folderPathsToDelete = [];

           foreach ($photoApprovals as $photo) {
               // Collect folder paths (extract folder path from storage_path)
               if ($photo->storage_path) {
                   // Extract folder path (everything before the filename)
                   $folderPath = dirname($photo->storage_path);
                   if ($folderPath && $folderPath !== '.' && !in_array($folderPath, $folderPathsToDelete)) {
                       $folderPathsToDelete[] = $folderPath;
                   }
               }
           }

           // Delete the database record first
           $gasIn->delete();

           // Delete folders from Google Drive (this will delete all files in the folders)
           $deletedFolders = 0;
           if (!empty($folderPathsToDelete)) {
               $deletedFolders = $this->deleteFoldersFromDrive($folderPathsToDelete);
           }

           $this->audit('delete', $gasIn, $old, [
               'deleted_folders' => $deletedFolders,
               'attempted_folders' => count($folderPathsToDelete)
           ]);

           return response()->json([
               'success' => true,
               'deleted' => true,
               'files_deleted' => $deletedFolders > 0 ? 'All files in folders' : 0,
               'folders_deleted' => $deletedFolders
           ]);

       } catch (\Exception $e) {
           Log::error('Gas In delete error', [
               'gas_in_id' => $gasIn->id,
               'error' => $e->getMessage()
           ]);

           return response()->json([
               'success' => false,
               'message' => 'Terjadi kesalahan saat menghapus data dan file terkait.'
           ], 500);
       }
   }

   public function redirectByReff(string $reffId)
   {
       try {
           $normalizedReff = strtoupper(trim($reffId));
           $gasIn = GasInData::where('reff_id_pelanggan', $normalizedReff)
                                  ->whereNull('deleted_at')
                                  ->first();

           if (!$gasIn && ctype_digit($normalizedReff)) {
               $gasIn = GasInData::whereRaw('CAST(reff_id_pelanggan AS UNSIGNED) = ?', [(int)$normalizedReff])
                                  ->whereNull('deleted_at')
                                  ->first();
           }

           if (!$gasIn) {
               return redirect()->route('gas-in.index')
                   ->with('error', "Gas In dengan Reference ID '{$reffId}' tidak ditemukan.");
           }

           return redirect()->route('gas-in.show', $gasIn->id);

       } catch (\Exception $e) {
           Log::error('Gas In redirectByReff failed', [
               'reff_id' => $reffId,
               'error' => $e->getMessage()
           ]);

           return redirect()->route('gas-in.index')
               ->with('error', 'Terjadi kesalahan saat mencari Gas In.');
       }
   }

   public function precheckGeneric(Request $r)
   {
       $v = Validator::make($r->all(), [
           'file'      => ['required','file','mimes:jpg,jpeg,png,webp,pdf','max:35840'],
           'slot_type' => ['required','string','max:100'],
           'module'    => ['nullable','in:GAS_IN'],
       ]);
       if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

       $module    = (string) $r->input('module', 'GAS_IN');
       $slotInput = (string) $r->input('slot_type');

       $cfgAll     = config('aergas_photos') ?: [];
       $moduleKey  = strtoupper($module);
       $slotCfg    = (array) (data_get($cfgAll, "modules.$moduleKey.slots.$slotInput") ?? []);

       if (!$slotCfg) {
           return response()->json(['success'=>false,'message'=>"Slot tidak dikenal: {$slotInput}"], 422);
       }

       $customPrompt = $slotCfg['prompt'] ?? null;
       if (!$customPrompt) {
           return response()->json([
               'success' => false,
               'message' => "Prompt validasi tidak dikonfigurasi untuk slot: {$slotInput}"
           ], 422);
       }

       try {
           $openAIService = app(OpenAIService::class);

           $result = $openAIService->validatePhotoWithPrompt(
               imagePath: $r->file('file')->getRealPath(),
               customPrompt: $customPrompt,
               context: [
                   'module' => $moduleKey,
                   'slot' => $slotInput,
                   'label' => $slotCfg['label'] ?? $slotInput
               ]
           );

           return response()->json([
               'success' => true,
               'ai' => [
                   'passed'     => $result['passed'],
                   'score'      => $result['confidence'] * 100,
                   'reason'     => $result['reason'],
                   'messages'   => [$result['reason']],
                   'objects'    => [],
                   'rules'      => [$slotInput],
                   'confidence' => $result['confidence'],
               ],
               'message' => $result['passed']
                   ? 'Validasi AI berhasil - foto diterima'
                   : 'Validasi AI gagal - ' . $result['reason'],
               'debug' => [
                   'prompt_used' => $customPrompt,
                   'raw_response' => $result['raw_response'] ?? null,
               ]
           ]);

       } catch (\Exception $e) {
           Log::error('Photo precheck failed', [
               'slot' => $slotInput,
               'module' => $moduleKey,
               'error' => $e->getMessage()
           ]);

           return response()->json([
               'success' => false,
               'message' => 'Validasi AI gagal: ' . $e->getMessage(),
               'ai' => [
                   'passed' => false,
                   'score' => 0,
                   'reason' => 'Error during AI validation',
                   'messages' => ['Terjadi kesalahan saat validasi AI'],
                   'objects' => [],
                   'rules' => [$slotInput],
               ]
           ], 500);
       }
   }

   public function uploadAndValidate(Request $r, GasInData $gasIn)
   {
       $v = Validator::make($r->all(), [
           'file'       => ['required','file','mimes:jpg,jpeg,png,webp,pdf','max:35840'],
           'slot_type'  => ['required','string','max:100'],
           'ai_passed'  => ['nullable','boolean'],
           'ai_score'   => ['nullable','numeric'],
           'ai_reason'  => ['nullable','string'],
           'ai_notes'   => ['nullable','array'],
       ]);
       if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

       $slotParam = (string) $r->input('slot_type');
       $slotSlug  = Str::slug($slotParam, '_');

       $ext  = strtolower($r->file('file')->getClientOriginalExtension() ?: $r->file('file')->extension());
       $ts   = now()->format('Ymd_His');
       $targetName = "{$gasIn->reff_id_pelanggan}_{$slotSlug}_{$ts}.{$ext}";

       $svc = $this->photoSvc ?? app(PhotoApprovalService::class);

       $meta = [];
       if ($gasIn->calonPelanggan) {
           $meta['customer_name'] = $gasIn->calonPelanggan->nama_pelanggan;
       }

       $res = $svc->storeWithoutAi(
           module: 'GAS_IN',
           reffId: $gasIn->reff_id_pelanggan,
           slotIncoming: $slotParam,
           file: $r->file('file'),
           uploadedBy: Auth::id(),
           targetFileName: $targetName,
           precheck: [
               'ai_passed'  => (bool) $r->boolean('ai_passed'),
               'ai_score'   => $r->input('ai_score'),
               'ai_reason'  => $r->input('ai_reason'),
               'ai_notes'   => $r->input('ai_notes', []),
           ],
           meta: $meta
       );

       $this->recalcGasInStatus($gasIn);

       return response()->json([
           'success'   => true,
           'photo_id'  => $res['photo_id'] ?? null,
           'filename'  => $targetName,
           'message'   => 'Upload berhasil, foto lama telah diganti',
           'ai_result' => [
               'passed' => (bool) $r->boolean('ai_passed'),
               'reason' => $r->input('ai_reason', 'No reason provided'),
               'score'  => $r->input('ai_score', 0),
           ]
       ], 201);
   }

   public function readyStatus(GasInData $gasIn)
   {
       return response()->json([
           'all_passed' => $gasIn->isAllPhotosPassed(),
           'can_submit' => $gasIn->canSubmit(),
           'ai_overall' => $gasIn->ai_overall_status ?? null,
           'status' => $gasIn->status,
       ]);
   }

   public function approveTracer(Request $r, GasInData $gasIn)
   {
       if (!$gasIn->canApproveTracer()) {
           return response()->json(['success'=>false,'message' => 'Belum siap untuk approval tracer.'], 422);
       }
       $old = $gasIn->getOriginal();
       $gasIn->status = GasInData::STATUS_TRACER_APPROVED;
       $gasIn->tracer_approved_at = now();
       $gasIn->tracer_approved_by = Auth::id();
       $gasIn->tracer_notes = $r->input('notes');
       $gasIn->save();
       $this->audit('approve', $gasIn, $old, $gasIn->toArray());
       return response()->json(['success'=>true,'data'=>$gasIn]);
   }

   public function rejectTracer(Request $r, GasInData $gasIn)
   {
       if ($gasIn->status !== GasInData::STATUS_READY_FOR_TRACER) {
           return response()->json(['success'=>false,'message' => 'Status tidak valid untuk reject tracer.'], 422);
       }
       $old = $gasIn->getOriginal();
       $gasIn->status = GasInData::STATUS_TRACER_REJECTED;
       $gasIn->tracer_notes = $r->input('notes');
       $gasIn->save();
       $this->audit('reject', $gasIn, $old, $gasIn->toArray());
       return response()->json(['success'=>true,'data'=>$gasIn]);
   }

   public function approveCgp(Request $r, GasInData $gasIn)
   {
       if (!$gasIn->canApproveCgp()) {
           return response()->json(['success'=>false,'message' => 'Belum siap untuk approval CGP.'], 422);
       }
       $old = $gasIn->getOriginal();
       $gasIn->status = GasInData::STATUS_CGP_APPROVED;
       $gasIn->cgp_approved_at = now();
       $gasIn->cgp_approved_by = Auth::id();
       $gasIn->cgp_notes = $r->input('notes');
       $gasIn->save();

       // Update customer progress_status to done (Gas-In is the last module)
       $customer = $gasIn->calonPelanggan;
       if ($customer && $customer->progress_status === 'gas_in') {
           $customer->progress_status = 'done';
           $customer->status = 'lanjut'; // Keep status as 'lanjut' (completed successfully)
           $customer->save();
       }

       $this->audit('approve', $gasIn, $old, $gasIn->toArray());
       return response()->json(['success'=>true,'data'=>$gasIn]);
   }

   public function rejectCgp(Request $r, GasInData $gasIn)
   {
       if ($gasIn->status !== GasInData::STATUS_TRACER_APPROVED) {
           return response()->json(['success'=>false,'message' => 'Status tidak valid untuk reject CGP.'], 422);
       }
       $old = $gasIn->getOriginal();
       $gasIn->status = GasInData::STATUS_CGP_REJECTED;
       $gasIn->cgp_notes = $r->input('notes');
       $gasIn->save();
       $this->audit('reject', $gasIn, $old, $gasIn->toArray());
       return response()->json(['success'=>true,'data'=>$gasIn]);
   }

   public function schedule(Request $r, GasInData $gasIn)
   {
       if (!$gasIn->canSchedule()) {
           return response()->json(['success'=>false,'message' => 'Belum bisa dijadwalkan.'], 422);
       }

       $v = Validator::make($r->all(), [
           'tanggal_gas_in' => ['required','date'],
       ]);
       if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

       $old = $gasIn->getOriginal();
       $gasIn->fill($v->validated());
       $gasIn->status = GasInData::STATUS_SCHEDULED;
       $gasIn->save();

       $this->audit('update', $gasIn, $old, $gasIn->toArray());
       return response()->json(['success'=>true,'data'=>$gasIn]);
   }

   public function uploadDraft(Request $r, GasInData $gasIn)
    {
        $v = Validator::make($r->all(), [
            'file' => ['required','file','mimes:jpg,jpeg,png,webp,pdf','max:35840'],
            'slot_type' => ['required','string','max:100'],
        ]);

        if ($v->fails()) {
            return response()->json(['success'=>false,'errors'=>$v->errors()], 422);
        }

        $slotParam = $r->input('slot_type');
        $slotSlug = Str::slug($slotParam, '_');
        $ext = strtolower($r->file('file')->getClientOriginalExtension() ?: $r->file('file')->extension());
        $ts = now()->format('Ymd_His');
        $targetName = "{$gasIn->reff_id_pelanggan}_{$slotSlug}_{$ts}.{$ext}";

        $svc = $this->photoSvc ?? app(PhotoApprovalService::class);

        $meta = [];
        if ($gasIn->calonPelanggan) {
            $meta['customer_name'] = $gasIn->calonPelanggan->nama_pelanggan;
        }

        $res = $svc->uploadDraftOnly(
            module: 'GAS_IN',
            reffId: $gasIn->reff_id_pelanggan,
            slotIncoming: $slotParam,
            file: $r->file('file'),
            uploadedBy: Auth::id(),
            targetFileName: $targetName,
            meta: $meta
        );

        return response()->json([
            'success' => true,
            'photo_id' => $res['photo_id'] ?? null,
            'filename' => $targetName,
            'message' => 'Upload berhasil tersimpan sebagai draft'
        ], 201);
    }

   public function complete(Request $r, GasInData $gasIn)
   {
       if (!$gasIn->canComplete()) {
           return response()->json(['success'=>false,'message' => 'Belum bisa diselesaikan.'], 422);
       }
       $old = $gasIn->getOriginal();
       $gasIn->status = GasInData::STATUS_COMPLETED;
       $gasIn->module_status = 'completed';
       $gasIn->save();

       // Update customer progress_status to done (Gas-In is the last module)
       $customer = $gasIn->calonPelanggan;
       if ($customer && $customer->progress_status === 'gas_in') {
           $customer->progress_status = 'done';
           $customer->status = 'lanjut'; // Keep status as 'lanjut' (completed successfully)
           $customer->save();
       }

       $this->audit('update', $gasIn, $old, $gasIn->toArray());
       return response()->json(['success'=>true,'data'=>$gasIn]);
   }

   /**
    * Preview data foto regulator sebelum download
    * Return JSON dengan list customer yang akan didownload
    */
   public function previewFotoRegulator(Request $r)
   {
       $v = Validator::make($r->all(), [
           'tanggal_dari' => ['nullable', 'date'],
           'tanggal_sampai' => ['nullable', 'date'],
       ]);

       if ($v->fails()) {
           return response()->json(['success' => false, 'errors' => $v->errors()], 422);
       }

       // Query Gas In dengan filter tanggal
       $query = GasInData::with(['calonPelanggan:reff_id_pelanggan,nama_pelanggan']);

       if ($r->filled('tanggal_dari')) {
           $query->whereDate('tanggal_gas_in', '>=', $r->tanggal_dari);
       }

       if ($r->filled('tanggal_sampai')) {
           $query->whereDate('tanggal_gas_in', '<=', $r->tanggal_sampai);
       }

       $gasInRecords = $query->get();

       if ($gasInRecords->isEmpty()) {
           return response()->json([
               'success' => false,
               'message' => 'Tidak ada data Gas In pada rentang tanggal yang dipilih.'
           ], 404);
       }

       // Ambil semua foto regulator
       $photoApprovals = \App\Models\PhotoApproval::whereIn('reff_id_pelanggan', $gasInRecords->pluck('reff_id_pelanggan'))
           ->where('module_name', 'gas_in')
           ->where('photo_field_name', 'foto_regulator')
           ->whereNotNull('photo_url')
           ->get();

       if ($photoApprovals->isEmpty()) {
           return response()->json([
               'success' => false,
               'message' => 'Tidak ada foto regulator yang ditemukan untuk rentang tanggal yang dipilih.'
           ], 404);
       }

       // Build preview data
       $previewData = [];
       foreach ($photoApprovals as $photo) {
           $customer = $gasInRecords->firstWhere('reff_id_pelanggan', $photo->reff_id_pelanggan);
           if ($customer && $customer->calonPelanggan) {
               $ext = pathinfo(parse_url($photo->photo_url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
               $namaPelangganSlug = \Illuminate\Support\Str::slug($customer->calonPelanggan->nama_pelanggan, '_');
               $tanggalGasIn = $customer->tanggal_gas_in ? \Carbon\Carbon::parse($customer->tanggal_gas_in)->format('Ymd') : 'NoDate';

               $previewData[] = [
                   'reff_id' => $photo->reff_id_pelanggan,
                   'nama_pelanggan' => $customer->calonPelanggan->nama_pelanggan,
                   'tanggal_gas_in' => $customer->tanggal_gas_in ? $customer->tanggal_gas_in->format('d-m-Y') : '-',
                   'filename' => "{$photo->reff_id_pelanggan}_{$namaPelangganSlug}_{$tanggalGasIn}_MGRT.{$ext}",
                   'photo_status' => $photo->photo_status ?? '-',
               ];
           }
       }

       return response()->json([
           'success' => true,
           'total_files' => count($previewData),
           'data' => $previewData,
           'filters' => [
               'tanggal_dari' => $r->tanggal_dari ?? null,
               'tanggal_sampai' => $r->tanggal_sampai ?? null,
           ]
       ]);
   }

   /**
    * Download foto regulator (MGRT) dari Gas In dengan filter rentang waktu
    * Format penamaan: {reff_id}_{nama_pelanggan}_MGRT.{ext}
    */
   public function downloadFotoRegulator(Request $r)
   {
       // Set execution time dan memory limit untuk handling batch download
       set_time_limit(300); // 5 minutes
       ini_set('memory_limit', '512M');

       $v = Validator::make($r->all(), [
           'tanggal_dari' => ['nullable', 'date'],
           'tanggal_sampai' => ['nullable', 'date'],
       ]);

       if ($v->fails()) {
           return response()->json(['success' => false, 'errors' => $v->errors()], 422);
       }

       // Query Gas In dengan filter tanggal
       $query = GasInData::with(['calonPelanggan:reff_id_pelanggan,nama_pelanggan']);

       if ($r->filled('tanggal_dari')) {
           $query->whereDate('tanggal_gas_in', '>=', $r->tanggal_dari);
       }

       if ($r->filled('tanggal_sampai')) {
           $query->whereDate('tanggal_gas_in', '<=', $r->tanggal_sampai);
       }

       $gasInRecords = $query->get();

       Log::info('Download Foto MGRT - Starting', [
           'total_gas_in_records' => $gasInRecords->count(),
           'tanggal_dari' => $r->tanggal_dari,
           'tanggal_sampai' => $r->tanggal_sampai
       ]);

       if ($gasInRecords->isEmpty()) {
           return response()->json([
               'success' => false,
               'message' => 'Tidak ada data Gas In pada rentang tanggal yang dipilih.'
           ], 404);
       }

       // Ambil semua foto regulator
       $photoApprovals = \App\Models\PhotoApproval::whereIn('reff_id_pelanggan', $gasInRecords->pluck('reff_id_pelanggan'))
           ->where('module_name', 'gas_in')
           ->where('photo_field_name', 'foto_regulator')
           ->whereNotNull('photo_url')
           ->get();

       if ($photoApprovals->isEmpty()) {
           return response()->json([
               'success' => false,
               'message' => 'Tidak ada foto regulator yang ditemukan untuk rentang tanggal yang dipilih.'
           ], 404);
       }

       // Buat ZIP file
       $zipFileName = 'Foto_MGRT_' . now()->format('Ymd_His') . '.zip';
       $zipPath = storage_path('app/temp/' . $zipFileName);

       // Pastikan folder temp ada
       if (!file_exists(storage_path('app/temp'))) {
           mkdir(storage_path('app/temp'), 0755, true);
       }

       $zip = new \ZipArchive();
       if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
           return response()->json([
               'success' => false,
               'message' => 'Gagal membuat file ZIP.'
           ], 500);
       }

       $addedCount = 0;
       $skippedCount = 0;
       $googleDriveService = app(\App\Services\GoogleDriveService::class);

       Log::info('Download Foto MGRT - Processing photos', [
           'total_photos' => $photoApprovals->count()
       ]);

       foreach ($photoApprovals as $index => $photo) {
           try {
               // Progress log setiap 10 files
               if (($index + 1) % 10 === 0) {
                   Log::info("Processing photo {$index}/{$photoApprovals->count()}");
               }

               // Cari customer data
               $customer = $gasInRecords->firstWhere('reff_id_pelanggan', $photo->reff_id_pelanggan);
               if (!$customer || !$customer->calonPelanggan) {
                   $skippedCount++;
                   continue;
               }

               $namaCustomer = $customer->calonPelanggan->nama_pelanggan;
               $reffId = $photo->reff_id_pelanggan;
               $tanggalGasIn = $customer->tanggal_gas_in ? \Carbon\Carbon::parse($customer->tanggal_gas_in)->format('Ymd') : 'NoDate';

               // Deteksi ekstensi file
               $ext = pathinfo(parse_url($photo->photo_url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';

               // Format: {reff_id}_{nama_pelanggan}_{tanggal_gas_in}_MGRT.{ext}
               $namaPelangganSlug = \Illuminate\Support\Str::slug($namaCustomer, '_');
               $newFileName = "{$reffId}_{$namaPelangganSlug}_{$tanggalGasIn}_MGRT.{$ext}";

               // Download file content
               $fileContent = null;

               // Cek apakah dari Google Drive
               if ($photo->drive_file_id) {
                   try {
                       $fileContent = $googleDriveService->downloadFileContent($photo->drive_file_id);
                   } catch (\Exception $e) {
                       Log::warning('Failed to download from Google Drive', [
                           'drive_file_id' => $photo->drive_file_id,
                           'error' => $e->getMessage()
                       ]);
                   }
               }

               // Fallback ke storage lokal
               if (!$fileContent && $photo->organization_path) {
                   $localPath = storage_path('app/public/' . $photo->organization_path);
                   if (file_exists($localPath)) {
                       $fileContent = file_get_contents($localPath);
                   }
               }

               // Fallback ke photo_url jika HTTP/HTTPS
               if (!$fileContent && filter_var($photo->photo_url, FILTER_VALIDATE_URL)) {
                   try {
                       $fileContent = file_get_contents($photo->photo_url);
                   } catch (\Exception $e) {
                       Log::warning('Failed to download from URL', [
                           'url' => $photo->photo_url,
                           'error' => $e->getMessage()
                       ]);
                   }
               }

               if ($fileContent) {
                   $zip->addFromString($newFileName, $fileContent);
                   $addedCount++;

                   // Free memory
                   unset($fileContent);
               } else {
                   $skippedCount++;
                   Log::warning('No file content for photo', [
                       'photo_id' => $photo->id,
                       'reff_id' => $photo->reff_id_pelanggan
                   ]);
               }

           } catch (\Exception $e) {
               $skippedCount++;
               Log::error('Error adding photo to ZIP: ' . $e->getMessage(), [
                   'photo_id' => $photo->id,
                   'reff_id' => $photo->reff_id_pelanggan,
                   'trace' => $e->getTraceAsString()
               ]);
           }
       }

       Log::info('Download Foto MGRT - Completed processing', [
           'added' => $addedCount,
           'skipped' => $skippedCount,
           'total' => $photoApprovals->count()
       ]);

       $zip->close();

       if ($addedCount === 0) {
           @unlink($zipPath);
           return response()->json([
               'success' => false,
               'message' => 'Tidak ada foto yang berhasil didownload.'
           ], 404);
       }

       // Return ZIP file sebagai download
       return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
   }

   private function buildPhotoDefs(string $module): array
   {
       $cfgAll    = config('aergas_photos') ?: [];
       $moduleKey = strtoupper((string) $module);
       $cfgSlots  = (array) (
           data_get($cfgAll, "modules.$moduleKey.slots")
           ?? data_get($cfgAll, 'modules.'.strtolower($module).'.slots', [])
       );

       $defs = [];
       foreach ($cfgSlots as $key => $rule) {
           $accept = $rule['accept'] ?? ['image/*'];
           if (is_string($accept)) $accept = [$accept];

           $checks = collect($rule['checks'] ?? [])
               ->map(fn($c) => $c['label'] ?? $c['id'] ?? '')
               ->filter()->values()->all();

           $defs[] = [
               'field' => $key,
               'label' => $rule['label'] ?? $key,
               'accept' => $accept,
               'required_objects' => $checks,
           ];
       }
       return $defs;
   }

   private function recalcGasInStatus(GasInData $gasIn): void
   {
       try {
           if (method_exists($gasIn, 'syncModuleStatusFromPhotos')) {
               $gasIn->syncModuleStatusFromPhotos();
           } else {
               if (method_exists($gasIn, 'recomputeAiOverallStatus')) {
                   $gasIn->recomputeAiOverallStatus();
               }
               if ($gasIn->status === GasInData::STATUS_DRAFT && $gasIn->canSubmit()) {
                   $gasIn->status = GasInData::STATUS_READY_FOR_TRACER;
                   $gasIn->save();
               }
           }
       } catch (\Throwable $e) {
           Log::info('recalcGasInStatus soft-failed', ['err' => $e->getMessage()]);
       }
   }

   private function audit(string $action, $model, array $old = [], array $new = []): void
   {
       try {
           $serviceKey = 'App\Services\AergasAuditService';
           if (app()->bound($serviceKey)) {
               app($serviceKey)->logModel($action, $model, $old, $new);
           } else {
               Log::info('audit', [
                   'action' => $action,
                   'model'  => class_basename($model),
                   'id'     => $model->getKey(),
                   'old'    => $old,
                   'new'    => $new,
               ]);
           }
       } catch (\Throwable $e) {
           Log::error('audit-failed: '.$e->getMessage());
       }
   }

   public function previewBeritaAcara(GasInData $gasIn, BeritaAcaraService $beritaAcaraService)
   {
       try {
           $result = $beritaAcaraService->generateGasInBeritaAcara($gasIn);

           if (!$result['success']) {
               return response()->json([
                   'success' => false,
                   'message' => $result['message']
               ], 422);
           }

           // Stream PDF to browser instead of download
           return $result['pdf']->stream($result['filename']);

       } catch (\Exception $e) {
           Log::error('Preview Gas In Berita Acara failed', [
               'gas_in_id' => $gasIn->id,
               'error' => $e->getMessage()
           ]);

           return response()->json([
               'success' => false,
               'message' => 'Gagal preview Berita Acara: ' . $e->getMessage()
           ], 500);
       }
   }

   public function generateBeritaAcara(GasInData $gasIn, BeritaAcaraService $beritaAcaraService)
   {
       try {
           $result = $beritaAcaraService->generateGasInBeritaAcara($gasIn);

           if (!$result['success']) {
               return response()->json([
                   'success' => false,
                   'message' => $result['message']
               ], 422);
           }

           return $result['pdf']->download($result['filename']);

       } catch (\Exception $e) {
           Log::error('Generate Gas In Berita Acara failed', [
               'gas_in_id' => $gasIn->id,
               'error' => $e->getMessage()
           ]);

           return response()->json([
               'success' => false,
               'message' => 'Gagal generate Berita Acara: ' . $e->getMessage()
           ], 500);
       }
   }

   /**
    * Download multiple Berita Acara as ZIP
    */
   public function downloadBulkBeritaAcara(Request $r, BeritaAcaraService $beritaAcaraService)
   {
       try {
           // Validate request
           $ids = $r->input('ids', []);

           if (empty($ids) || !is_array($ids)) {
               return response()->json([
                   'success' => false,
                   'message' => 'Tidak ada ID yang dipilih'
               ], 422);
           }

           // Limit to prevent timeout (max 100 files)
           if (count($ids) > 100) {
               return response()->json([
                   'success' => false,
                   'message' => 'Maksimal 100 BA dapat di-download sekaligus'
               ], 422);
           }

           // Fetch Gas In data
           $gasInItems = GasInData::with('calonPelanggan')
               ->whereIn('id', $ids)
               ->get();

           if ($gasInItems->isEmpty()) {
               return response()->json([
                   'success' => false,
                   'message' => 'Data tidak ditemukan'
               ], 404);
           }

           // Create temporary directory for PDFs
           $tempDir = storage_path('app/temp/ba_bulk_' . uniqid());
           if (!file_exists($tempDir)) {
               mkdir($tempDir, 0755, true);
           }

           $generatedFiles = [];
           $errors = [];

           // Generate each PDF
           foreach ($gasInItems as $gasIn) {
               try {
                   $result = $beritaAcaraService->generateGasInBeritaAcara($gasIn);

                   if ($result['success']) {
                       $filename = $result['filename'];
                       $filePath = $tempDir . '/' . $filename;

                       // Save PDF to temp directory
                       $result['pdf']->save($filePath);
                       $generatedFiles[] = $filePath;
                   } else {
                       $errors[] = "BA untuk {$gasIn->reff_id_pelanggan}: {$result['message']}";
                   }
               } catch (\Exception $e) {
                   $errors[] = "BA untuk {$gasIn->reff_id_pelanggan}: {$e->getMessage()}";
                   Log::error('Failed to generate BA in bulk', [
                       'gas_in_id' => $gasIn->id,
                       'error' => $e->getMessage()
                   ]);
               }
           }

           if (empty($generatedFiles)) {
               // Clean up temp directory
               $this->deleteDirectory($tempDir);

               return response()->json([
                   'success' => false,
                   'message' => 'Tidak ada BA yang berhasil di-generate',
                   'errors' => $errors
               ], 500);
           }

           // Create ZIP file
           $zipFilename = 'Berita_Acara_Gas_In_' . date('Ymd_His') . '.zip';
           $zipPath = storage_path('app/temp/' . $zipFilename);

           $zip = new \ZipArchive();
           if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
               $this->deleteDirectory($tempDir);
               return response()->json([
                   'success' => false,
                   'message' => 'Gagal membuat file ZIP'
               ], 500);
           }

           // Add files to ZIP
           foreach ($generatedFiles as $file) {
               $zip->addFile($file, basename($file));
           }

           $zip->close();

           // Clean up temp directory with PDFs
           $this->deleteDirectory($tempDir);

           // Return ZIP file for download
           return response()->download($zipPath, $zipFilename)->deleteFileAfterSend(true);

       } catch (\Exception $e) {
           Log::error('Bulk BA Download failed', [
               'error' => $e->getMessage(),
               'trace' => $e->getTraceAsString()
           ]);

           return response()->json([
               'success' => false,
               'message' => 'Terjadi kesalahan: ' . $e->getMessage()
           ], 500);
       }
   }

   /**
    * Helper function to delete directory recursively
    */
   private function deleteDirectory($dir)
   {
       if (!file_exists($dir)) {
           return true;
       }

       if (!is_dir($dir)) {
           return unlink($dir);
       }

       foreach (scandir($dir) as $item) {
           if ($item == '.' || $item == '..') {
               continue;
           }

           if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
               return false;
           }
       }

       return rmdir($dir);
   }

   /**
    * Delete folders from Google Drive
    */
   private function deleteFoldersFromDrive(array $folderPaths): int
   {
       $deletedCount = 0;

       try {
           $googleDriveService = app(\App\Services\GoogleDriveService::class);

           foreach ($folderPaths as $folderPath) {
               try {
                   if ($googleDriveService->deleteFolder($folderPath)) {
                       $deletedCount++;
                       Log::info('Deleted folder from Google Drive', ['folder_path' => $folderPath]);
                   } else {
                       Log::warning('Failed to delete folder from Google Drive', [
                           'folder_path' => $folderPath,
                           'reason' => 'Service returned false'
                       ]);
                   }
               } catch (\Exception $e) {
                   Log::warning('Failed to delete folder from Google Drive', [
                       'folder_path' => $folderPath,
                       'error' => $e->getMessage()
                   ]);
               }
           }
       } catch (\Exception $e) {
           Log::error('Google Drive folder deletion error', [
               'folder_paths' => $folderPaths,
               'error' => $e->getMessage()
           ]);
       }

       return $deletedCount;
   }

   public function getRejectionDetails(GasInData $gasIn)
   {
       try {
           // Get config slots for GAS_IN module to get original labels
           $cfgSlots = (array) (config('aergas_photos.modules.GAS_IN.slots') ?? []);

           $rejectedPhotos = $gasIn->photoApprovals()
               ->where(function($q) {
                   $q->whereNotNull('tracer_rejected_at')
                     ->orWhereNotNull('cgp_rejected_at');
               })
               ->with(['tracerUser', 'cgpUser'])
               ->get();

           $rejections = $rejectedPhotos->map(function($photo) use ($cfgSlots) {
               $rejectedByType = null;
               $rejectedByName = null;
               $reason = null;
               $rejectedDate = null;
               $category = null;

               if ($photo->tracer_rejected_at) {
                   $rejectedByType = 'tracer';
                   $rejectedByName = $photo->tracerUser->name ?? 'Unknown';
                   $reason = $photo->tracer_notes;
                   $rejectedDate = $photo->tracer_rejected_at->format('d/m/Y H:i');
                   $category = $photo->tracer_rejection_category;
               } elseif ($photo->cgp_rejected_at) {
                   $rejectedByType = 'cgp';
                   $rejectedByName = $photo->cgpUser->name ?? 'Unknown';
                   $reason = $photo->cgp_notes;
                   $rejectedDate = $photo->cgp_rejected_at->format('d/m/Y H:i');
                   $category = $photo->cgp_rejection_category;
               }

               // Get original label from config
               $slotLabel = $cfgSlots[$photo->photo_field_name]['label'] ?? $photo->slot_label ?? $photo->photo_field_name;

               return [
                   'photo_field' => $photo->photo_field_name,
                   'slot_label' => $slotLabel,
                   'rejected_by_type' => $rejectedByType,
                   'rejected_by_name' => $rejectedByName,
                   'reason' => $reason,
                   'rejected_date' => $rejectedDate,
                   'category' => $category,
               ];
           });

           return response()->json([
               'success' => true,
               'rejections' => $rejections
           ]);

       } catch (\Exception $e) {
           Log::error('Get Gas In rejection details failed', [
               'gas_in_id' => $gasIn->id,
               'error' => $e->getMessage()
           ]);

           return response()->json([
               'success' => false,
               'message' => 'Failed to load rejection details'
           ], 500);
       }
   }

   /**
    * Get photo status details for a Gas In record
    */
   private function getPhotoStatusDetails(GasInData $gasIn): array
   {
       $requiredPhotos = $gasIn->getRequiredPhotos();
       $photoApprovals = $gasIn->photoApprovals;
       $statusDetails = [];

       foreach ($requiredPhotos as $photoField) {
           $photo = $photoApprovals->firstWhere('photo_field_name', $photoField);

           if (!$photo) {
               // Photo not uploaded
               $statusDetails[] = [
                   'field' => $photoField,
                   'label' => $this->getPhotoLabel($photoField),
                   'status' => 'missing',
                   'icon' => 'fa-times-circle',
                   'color' => 'text-red-600',
                   'bg' => 'bg-red-50'
               ];
           } else {
               // Check if photo has valid storage_path with filename
               $storagePath = $photo->storage_path ?? '';
               $basename = basename($storagePath);

               // Consider as valid file if:
               // 1. Has file extension (e.g., .jpg, .png, .pdf)
               // 2. Has dot in basename (even without extension, e.g., "file.")
               // 3. Has timestamp pattern (e.g., _20251004_131955)
               $hasValidFile = !empty($storagePath) && (
                   pathinfo($storagePath, PATHINFO_EXTENSION) !== '' ||
                   strpos($basename, '.') !== false ||
                   preg_match('/_\d{8}_\d{6}/', $basename)
               );

               if ($hasValidFile) {
                   $statusDetails[] = [
                       'field' => $photoField,
                       'label' => $this->getPhotoLabel($photoField),
                       'status' => 'uploaded',
                       'icon' => 'fa-check-circle',
                       'color' => 'text-green-600',
                       'bg' => 'bg-green-50',
                       'ai_status' => $photo->ai_status ?? null
                   ];
               } else {
                   // Corrupted: only folder path, no filename
                   $statusDetails[] = [
                       'field' => $photoField,
                       'label' => $this->getPhotoLabel($photoField),
                       'status' => 'corrupted',
                       'icon' => 'fa-exclamation-triangle',
                       'color' => 'text-yellow-600',
                       'bg' => 'bg-yellow-50'
                   ];
               }
           }
       }

       return $statusDetails;
   }

   /**
    * Get human-readable label for photo field from config
    */
   private function getPhotoLabel(string $fieldName): string
   {
       // Get label from config first
       $cfgSlots = (array) (config('aergas_photos.modules.GAS_IN.slots') ?? []);

       if (isset($cfgSlots[$fieldName]['label'])) {
           return $cfgSlots[$fieldName]['label'];
       }

       // Fallback to hardcoded labels (for backward compatibility)
       $labels = [
           'ba_gas_in' => 'BA Gas In',
           'foto_bubble_test' => 'Foto Bubble Test',
           'foto_regulator' => 'Foto Regulator',
           'foto_kompor_menyala' => 'Foto Kompor Menyala',
       ];

       return $labels[$fieldName] ?? ucwords(str_replace('_', ' ', $fieldName));
   }

   /**
    * Preview data sebelum export Excel
    */
   public function previewExportExcel(Request $r)
   {
       $v = Validator::make($r->all(), [
           'tanggal_dari' => ['nullable', 'date'],
           'tanggal_sampai' => ['nullable', 'date'],
           'module_status' => ['nullable', 'string'],
           'search' => ['nullable', 'string'],
       ]);

       if ($v->fails()) {
           return response()->json(['success' => false, 'errors' => $v->errors()], 422);
       }

       // Query Gas In
       $query = GasInData::with([
           'calonPelanggan:reff_id_pelanggan,nama_pelanggan,alamat,kelurahan',
           'srData:reff_id_pelanggan,no_seri_mgrt,merk_brand_mgrt'
       ]);

       // Apply filters
       if ($r->filled('tanggal_dari')) {
           $query->whereDate('tanggal_gas_in', '>=', $r->tanggal_dari);
       }

       if ($r->filled('tanggal_sampai')) {
           $query->whereDate('tanggal_gas_in', '<=', $r->tanggal_sampai);
       }

       if ($r->filled('module_status')) {
           $query->where('module_status', $r->module_status);
       }

       if ($r->filled('search')) {
           $search = $r->search;
           $query->whereHas('calonPelanggan', function($q) use ($search) {
               $q->where('nama_pelanggan', 'like', "%{$search}%")
                 ->orWhere('reff_id_pelanggan', 'like', "%{$search}%")
                 ->orWhere('alamat', 'like', "%{$search}%")
                 ->orWhere('no_telepon', 'like', "%{$search}%");
           });
       }

       $gasInRecords = $query->orderBy('tanggal_gas_in', 'desc')->get();

       if ($gasInRecords->isEmpty()) {
           return response()->json([
               'success' => false,
               'message' => 'Tidak ada data Gas In yang ditemukan untuk filter yang dipilih.'
           ], 404);
       }

       // Build preview data
       $previewData = $gasInRecords->map(function($gasIn) {
           return [
               'reff_id' => $gasIn->reff_id_pelanggan,
               'nama_pelanggan' => $gasIn->calonPelanggan->nama_pelanggan ?? '-',
               'alamat' => $gasIn->calonPelanggan->alamat ?? '-',
               'kelurahan' => $gasIn->calonPelanggan->kelurahan ?? '-',
               'tanggal_gas_in' => $gasIn->tanggal_gas_in ? $gasIn->tanggal_gas_in->format('d-m-Y') : '-',
               'no_seri_mgrt' => $gasIn->srData->no_seri_mgrt ?? '-',
               'module_status' => $gasIn->module_status,
           ];
       });

       return response()->json([
           'success' => true,
           'total_records' => $previewData->count(),
           'data' => $previewData,
           'filters' => [
               'tanggal_dari' => $r->tanggal_dari ?? null,
               'tanggal_sampai' => $r->tanggal_sampai ?? null,
               'module_status' => $r->module_status ?? null,
           ]
       ]);
   }

   /**
    * Export Gas In data to Excel
    */
   public function exportExcel(Request $r)
   {
       $v = Validator::make($r->all(), [
           'tanggal_dari' => ['nullable', 'date'],
           'tanggal_sampai' => ['nullable', 'date'],
           'module_status' => ['nullable', 'string'],
           'search' => ['nullable', 'string'],
       ]);

       if ($v->fails()) {
           return redirect()->back()->withErrors($v)->withInput();
       }

       $filters = [
           'tanggal_dari' => $r->tanggal_dari,
           'tanggal_sampai' => $r->tanggal_sampai,
           'module_status' => $r->module_status,
           'search' => $r->search,
       ];

       $fileName = 'Gas_In_Export_' . now()->format('Ymd_His') . '.xlsx';

       return \Maatwebsite\Excel\Facades\Excel::download(
           new \App\Exports\GasInExport($filters),
           $fileName
       );
   }

   /**
    * Download single foto MGRT (foto regulator) berdasarkan reff_id
    */
   public function downloadSingleFotoMGRT(Request $r)
   {
       $v = Validator::make($r->all(), [
           'reff_id' => ['required', 'string'],
       ]);

       if ($v->fails()) {
           return response()->json(['success' => false, 'errors' => $v->errors()], 422);
       }

       // Cari foto regulator berdasarkan reff_id
       $photo = \App\Models\PhotoApproval::where('reff_id_pelanggan', $r->reff_id)
           ->where('module_name', 'gas_in')
           ->where('photo_field_name', 'foto_regulator')
           ->whereNotNull('photo_url')
           ->first();

       if (!$photo) {
           return response()->json([
               'success' => false,
               'message' => 'Foto MGRT tidak ditemukan untuk Reff ID: ' . $r->reff_id
           ], 404);
       }

       // Get customer name dan tanggal untuk nama file
       $gasIn = GasInData::with('calonPelanggan')->where('reff_id_pelanggan', $r->reff_id)->first();
       $namaCustomer = $gasIn && $gasIn->calonPelanggan ? $gasIn->calonPelanggan->nama_pelanggan : 'Customer';
       $tanggalGasIn = $gasIn && $gasIn->tanggal_gas_in ? \Carbon\Carbon::parse($gasIn->tanggal_gas_in)->format('Ymd') : 'NoDate';

       // Download file content
       $fileContent = null;
       $googleDriveService = app(\App\Services\GoogleDriveService::class);

       // Try Google Drive
       if ($photo->drive_file_id) {
           try {
               $fileContent = $googleDriveService->downloadFileContent($photo->drive_file_id);
           } catch (\Exception $e) {
               Log::warning('Failed to download from Google Drive', [
                   'drive_file_id' => $photo->drive_file_id,
                   'error' => $e->getMessage()
               ]);
           }
       }

       // Fallback to local storage
       if (!$fileContent && $photo->organization_path) {
           $localPath = storage_path('app/public/' . $photo->organization_path);
           if (file_exists($localPath)) {
               $fileContent = file_get_contents($localPath);
           }
       }

       // Fallback to photo_url if HTTP/HTTPS
       if (!$fileContent && filter_var($photo->photo_url, FILTER_VALIDATE_URL)) {
           try {
               $fileContent = file_get_contents($photo->photo_url);
           } catch (\Exception $e) {
               Log::warning('Failed to download from URL', [
                   'url' => $photo->photo_url,
                   'error' => $e->getMessage()
               ]);
           }
       }

       if (!$fileContent) {
           return response()->json([
               'success' => false,
               'message' => 'Gagal mengunduh file foto MGRT.'
           ], 500);
       }

       // Deteksi ekstensi file
       $ext = pathinfo(parse_url($photo->photo_url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';

       // Format nama file: {reff_id}_{nama_pelanggan}_{tanggal}_MGRT.{ext}
       $namaPelangganSlug = \Illuminate\Support\Str::slug($namaCustomer, '_');
       $fileName = "{$r->reff_id}_{$namaPelangganSlug}_{$tanggalGasIn}_MGRT.{$ext}";

       // Return file download
       return response($fileContent)
           ->header('Content-Type', 'image/' . $ext)
           ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
   }

}
