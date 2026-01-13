<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\JalurTestPackage;
use App\Models\JalurCluster;
use App\Models\JalurLineNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JalurTestPackageController extends Controller
{
    public function index(Request $request)
    {
        // 1. Get Stats for Summary Cards
        $stats = [
            'total' => JalurTestPackage::count(),
            'flushing' => JalurTestPackage::where('status', 'flushing')->count(),
            'pneumatic' => JalurTestPackage::where('status', 'pneumatic')->count(),
            'gas_in' => JalurTestPackage::where('status', 'gas_in')->orWhere('status', 'completed')->count(),
        ];

        // 2. Get Data with Eager Loading (Nested) to calculate length efficiently
        $packages = JalurTestPackage::with(['cluster', 'items.lineNumber'])
            ->latest()
            ->paginate(10);

        return view('jalur.test-package.index', compact('packages', 'stats'));
    }

    public function create()
    {
        $clusters = JalurCluster::all();
        return view('jalur.test-package.create', compact('clusters'));
    }

    // API to get available lines for a cluster
    public function getAvailableLines($clusterId)
    {
        // Get Line Numbers that:
        // 1. Belong to this cluster
        // 2. Have Lowering Data that is APPROVED (acc_cgp)
        // 3. Are NOT already in another Test Package (optional, strictly speaking one line usually tested once in a package)

        $lines = JalurLineNumber::where('cluster_id', $clusterId)
            ->whereHas('loweringData', function ($q) {
                $q->where('status_laporan', 'acc_cgp');
            })
            ->whereDoesntHave('testPackageItems') // Assuming we want exclude lines already packaged
            ->with([
                'loweringData' => function ($q) {
                    $q->select('id', 'line_number_id', 'status_laporan', 'panjang_pipa');
                }
            ])
            ->get(['id', 'line_number', 'diameter', 'estimasi_panjang']);

        return response()->json($lines);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cluster_id' => 'required|exists:jalur_clusters,id',
            'test_package_code' => 'required|string|unique:jalur_test_packages,test_package_code',
            'line_number_ids' => 'required|array|min:1',
            'line_number_ids.*' => 'exists:jalur_line_numbers,id'
        ]);

        try {
            DB::beginTransaction();

            $package = JalurTestPackage::create([
                'cluster_id' => $request->cluster_id,
                'test_package_code' => $request->test_package_code,
                'status' => 'draft',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            foreach ($request->line_number_ids as $lineId) {
                $package->items()->create([
                    'line_number_id' => $lineId
                ]);
            }

            DB::commit();

            return redirect()->route('jalur.test-package.index')
                ->with('success', 'Test Package berhasil dibuat.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create test package: ' . $e->getMessage());
            return back()->with('error', 'Gagal membuat test package: ' . $e->getMessage());
        }
    }

    public function show(JalurTestPackage $testPackage)
    {
        // Load relationships including loweringData sum if possible, but basic load is fine.
        // We will calculate lengths in the view or via model accessor
        $testPackage->load(['cluster', 'items.lineNumber.loweringData']);
        return view('jalur.test-package.show', compact('testPackage'));
    }

    public function updateStep(Request $request, JalurTestPackage $testPackage, \App\Services\GoogleDriveService $driveService)
    {
        // This method handles updates for Flushing, Pneumatic, etc.
        // We can differentiate steps/forms by a hidden input or infer from data.

        $step = $request->input('step'); // flushing, pneumatic, purging, gas_in

        $rules = [];
        if ($step === 'flushing') {
            $rules['flushing_date'] = 'required|date';
            $rules['flushing_evidence'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240';
        } elseif ($step === 'pneumatic') {
            $rules['pneumatic_date'] = 'required|date';
            $rules['pneumatic_evidence'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240';
        } elseif ($step === 'purging') {
            $rules['purging_date'] = 'required|date';
            $rules['purging_evidence'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240';
        } elseif ($step === 'gas_in') {
            $rules['gas_in_date'] = 'required|date';
            $rules['gas_in_evidence'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240';
        }

        $validated = $request->validate($rules);

        // Handle File Upload - Google Drive Integration
        if ($request->hasFile("{$step}_evidence")) {
            $file = $request->file("{$step}_evidence");

            try {
                // Folder Structure: JALUR_TEST_DATA / ClusterName / PackageCode / Step
                $clusterName = $testPackage->cluster->nama_cluster ?? 'Unclustered';
                $packageCode = $testPackage->test_package_code;

                // Ensure sanitized path
                $folderPath = "JALUR_TEST_DATA/{$clusterName}/{$packageCode}/{$step}";

                // Get or Create Folder ID recursively
                $folderId = $driveService->ensureNestedFolders($folderPath);

                // Upload File
                $result = $driveService->uploadFile($file, $folderId);

                // Save Web View Link (URL) to database
                $testPackage->{"{$step}_evidence_path"} = $result['webViewLink'];

            } catch (\Exception $e) {
                Log::error("Failed to upload test package evidence to Drive: " . $e->getMessage());
                // Fallback to local storage if Drive fails (optional, or just throw error)
                // For now, let's notify user but maybe save locally as backup?
                // Let's just create a flash error and fallback local
                $path = $file->store("evidence/{$step}/{$testPackage->id}", 'public');
                $testPackage->{"{$step}_evidence_path"} = $path;

                // We will append warning message in return
                session()->flash('warning', 'Gagal upload ke Google Drive, file disimpan di server lokal sebagai backup. Error: ' . $e->getMessage());
            }
        }

        // Update Date & Notes
        if ($request->has("{$step}_date")) {
            $testPackage->{"{$step}_date"} = $request->input("{$step}_date");
        }
        if ($request->has("{$step}_notes")) {
            $testPackage->{"{$step}_notes"} = $request->input("{$step}_notes");
        }

        // Update Status Flow
        // Let's simplified: just update status to current step
        $testPackage->status = $step;

        // Logic to close
        if ($step === 'gas_in') {
            $testPackage->status = 'completed';
        }

        $testPackage->updated_by = Auth::id();
        $testPackage->save();

        return back()->with('success', "Data {$step} berhasil diupdate.");
    }
}
