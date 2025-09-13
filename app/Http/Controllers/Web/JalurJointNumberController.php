<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\JalurJointNumber;
use App\Models\JalurCluster;
use App\Models\JalurFittingType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JalurJointNumberController extends Controller
{
    public function index(Request $request)
    {
        $query = JalurJointNumber::query()->with(['cluster', 'fittingType', 'usedByJoint', 'createdBy']);

        if ($request->filled('search')) {
            $query->where('nomor_joint', 'like', "%{$request->search}%");
        }

        if ($request->filled('cluster_id')) {
            $query->byCluster($request->cluster_id);
        }

        if ($request->filled('fitting_type_id')) {
            $query->byFittingType($request->fitting_type_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'available') {
                $query->available();
            } elseif ($request->status === 'used') {
                $query->used();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $jointNumbers = $query->latest()->paginate(20);
        $clusters = JalurCluster::active()->get();
        $fittingTypes = JalurFittingType::active()->get();

        return view('jalur.joint-numbers.index', compact('jointNumbers', 'clusters', 'fittingTypes'));
    }

    public function create()
    {
        $clusters = JalurCluster::active()->get();
        $fittingTypes = JalurFittingType::active()->get();

        return view('jalur.joint-numbers.create', compact('clusters', 'fittingTypes'));
    }

    public function store(Request $request)
    {
        $generationType = $request->input('generation_type', 'single');
        
        $baseRules = [
            'cluster_id' => 'required|exists:jalur_clusters,id',
            'fitting_type_id' => 'required|exists:jalur_fitting_types,id',
            'generation_type' => 'required|in:single,batch',
            'is_active' => 'boolean',
        ];
        
        // Conditional validation based on generation type
        if ($generationType === 'single') {
            $baseRules['nomor_joint'] = 'required|string|max:50';
        } else {
            $baseRules['start_number'] = 'required|integer|min:1|max:999';
            $baseRules['end_number'] = 'required|integer|min:1|max:999|gte:start_number';
        }
        
        // Validate max 50 numbers for batch
        if ($generationType === 'batch') {
            $request->validate([
                'end_number' => 'max:' . ($request->start_number + 49)
            ], [
                'end_number.max' => 'Maksimal 50 nomor dapat dibuat sekaligus.'
            ]);
        }
        
        $validated = $request->validate($baseRules);

        try {
            DB::beginTransaction();

            $cluster = JalurCluster::findOrFail($validated['cluster_id']);
            $fittingType = JalurFittingType::findOrFail($validated['fitting_type_id']);
            
            $createdCount = 0;
            $skippedCount = 0;
            
            if ($generationType === 'single') {
                // Single mode: Parse manual nomor_joint input
                $nomorJoint = $validated['nomor_joint'];
                
                // Extract joint code from full nomor joint
                $expectedPrefix = "{$cluster->code_cluster}-{$fittingType->code_fitting}";
                if (!str_starts_with($nomorJoint, $expectedPrefix)) {
                    return back()
                        ->withInput()
                        ->with('error', "Nomor joint harus dimulai dengan {$expectedPrefix}");
                }
                
                $jointCode = substr($nomorJoint, strlen($expectedPrefix));
                
                // Check if already exists
                if (JalurJointNumber::where('nomor_joint', $nomorJoint)->exists()) {
                    return back()
                        ->withInput()
                        ->with('error', "Nomor joint {$nomorJoint} sudah ada.");
                }

                JalurJointNumber::create([
                    'cluster_id' => $validated['cluster_id'],
                    'fitting_type_id' => $validated['fitting_type_id'],
                    'nomor_joint' => $nomorJoint,
                    'joint_code' => $jointCode,
                    'is_active' => $request->boolean('is_active', true),
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
                
                $createdCount = 1;
                
            } else {
                // Batch mode: Generate from start_number to end_number
                for ($i = $validated['start_number']; $i <= $validated['end_number']; $i++) {
                    $jointCode = str_pad($i, 3, '0', STR_PAD_LEFT);
                    $nomorJoint = "{$cluster->code_cluster}-{$fittingType->code_fitting}{$jointCode}";
                    
                    // Check if already exists
                    if (JalurJointNumber::where('nomor_joint', $nomorJoint)->exists()) {
                        $skippedCount++;
                        continue;
                    }

                    JalurJointNumber::create([
                        'cluster_id' => $validated['cluster_id'],
                        'fitting_type_id' => $validated['fitting_type_id'],
                        'nomor_joint' => $nomorJoint,
                        'joint_code' => $jointCode,
                        'is_active' => $request->boolean('is_active', true),
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                    
                    $createdCount++;
                }
            }

            DB::commit();

            $message = "Berhasil membuat {$createdCount} nomor joint";
            if ($skippedCount > 0) {
                $message .= ". {$skippedCount} nomor sudah ada (dilewati)";
            }

            return redirect()
                ->route('jalur.joint-numbers.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()
                ->withInput()
                ->with('error', 'Gagal membuat nomor joint: ' . $e->getMessage());
        }
    }

    public function show(JalurJointNumber $jointNumber)
    {
        $jointNumber->load(['cluster', 'fittingType', 'usedByJoint', 'createdBy', 'updatedBy']);

        return view('jalur.joint-numbers.show', compact('jointNumber'));
    }

    public function edit(JalurJointNumber $jointNumber)
    {
        if ($jointNumber->is_used) {
            return back()->with('error', 'Nomor joint yang sudah digunakan tidak dapat diedit.');
        }

        $clusters = JalurCluster::active()->get();
        $fittingTypes = JalurFittingType::active()->get();

        return view('jalur.joint-numbers.edit', compact('jointNumber', 'clusters', 'fittingTypes'));
    }

    public function update(Request $request, JalurJointNumber $jointNumber)
    {
        if ($jointNumber->is_used) {
            return back()->with('error', 'Nomor joint yang sudah digunakan tidak dapat diedit.');
        }

        $validated = $request->validate([
            'cluster_id' => 'required|exists:jalur_clusters,id',
            'fitting_type_id' => 'required|exists:jalur_fitting_types,id',
            'joint_code' => 'required|string|max:10',
            'is_active' => 'boolean',
        ]);

        try {
            $cluster = JalurCluster::findOrFail($validated['cluster_id']);
            $fittingType = JalurFittingType::findOrFail($validated['fitting_type_id']);
            
            $nomorJoint = "{$cluster->code_cluster}-{$fittingType->code_fitting}{$validated['joint_code']}";
            
            // Check if nomor joint already exists (except current)
            if (JalurJointNumber::where('nomor_joint', $nomorJoint)->where('id', '!=', $jointNumber->id)->exists()) {
                return back()
                    ->withInput()
                    ->with('error', 'Nomor joint sudah ada.');
            }

            $jointNumber->update([
                'cluster_id' => $validated['cluster_id'],
                'fitting_type_id' => $validated['fitting_type_id'],
                'nomor_joint' => $nomorJoint,
                'joint_code' => $validated['joint_code'],
                'is_active' => $request->boolean('is_active', true),
                'updated_by' => auth()->id(),
            ]);

            return redirect()
                ->route('jalur.joint-numbers.show', $jointNumber)
                ->with('success', 'Nomor joint berhasil diperbarui.');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui nomor joint: ' . $e->getMessage());
        }
    }

    public function destroy(JalurJointNumber $jointNumber)
    {
        if ($jointNumber->is_used) {
            return back()->with('error', 'Nomor joint yang sudah digunakan tidak dapat dihapus.');
        }

        try {
            $jointNumber->delete();

            return redirect()
                ->route('jalur.joint-numbers.index')
                ->with('success', 'Nomor joint berhasil dihapus.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus nomor joint: ' . $e->getMessage());
        }
    }

    public function toggleStatus(JalurJointNumber $jointNumber)
    {
        try {
            $jointNumber->update([
                'is_active' => !$jointNumber->is_active,
                'updated_by' => auth()->id(),
            ]);

            $status = $jointNumber->is_active ? 'diaktifkan' : 'dinonaktifkan';

            return back()->with('success', "Nomor joint berhasil {$status}.");

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengubah status nomor joint: ' . $e->getMessage());
        }
    }

    public function batchCreate(Request $request)
    {
        $validated = $request->validate([
            'cluster_id' => 'required|exists:jalur_clusters,id',
            'fitting_type_id' => 'required|exists:jalur_fitting_types,id',
            'start_number' => 'required|integer|min:1',
            'end_number' => 'required|integer|min:1|gte:start_number',
        ]);

        try {
            DB::beginTransaction();

            $cluster = JalurCluster::findOrFail($validated['cluster_id']);
            $fittingType = JalurFittingType::findOrFail($validated['fitting_type_id']);
            
            $createdCount = 0;
            $skippedCount = 0;

            for ($i = $validated['start_number']; $i <= $validated['end_number']; $i++) {
                $code = str_pad($i, 3, '0', STR_PAD_LEFT);
                $nomorJoint = "{$cluster->code_cluster}-{$fittingType->code_fitting}{$code}";
                
                if (JalurJointNumber::where('nomor_joint', $nomorJoint)->exists()) {
                    $skippedCount++;
                    continue;
                }

                JalurJointNumber::create([
                    'cluster_id' => $validated['cluster_id'],
                    'fitting_type_id' => $validated['fitting_type_id'],
                    'nomor_joint' => $nomorJoint,
                    'joint_code' => $code,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
                
                $createdCount++;
            }

            DB::commit();

            $message = "Berhasil membuat {$createdCount} nomor joint";
            if ($skippedCount > 0) {
                $message .= ". {$skippedCount} nomor sudah ada (dilewati)";
            }

            return response()->json(['success' => true, 'message' => $message]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // API endpoint untuk dropdown joint numbers
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

    private function parseJointCodes(string $input): array
    {
        $codes = [];
        $parts = explode(',', $input);
        
        foreach ($parts as $part) {
            $part = trim($part);
            
            // Handle range format "001-010"
            if (preg_match('/^(\d+)-(\d+)$/', $part, $matches)) {
                $start = (int) $matches[1];
                $end = (int) $matches[2];
                
                for ($i = $start; $i <= $end; $i++) {
                    $codes[] = str_pad($i, 3, '0', STR_PAD_LEFT);
                }
            } else {
                // Single code
                $codes[] = str_pad($part, 3, '0', STR_PAD_LEFT);
            }
        }
        
        return array_unique($codes);
    }
}