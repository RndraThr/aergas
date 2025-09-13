<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\JalurCluster;
use App\Models\JalurLineNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JalurLineNumberController extends Controller
{

    public function index(Request $request)
    {
        $query = JalurLineNumber::query()->with(['cluster', 'createdBy']);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('cluster_id')) {
            $query->byCluster($request->cluster_id);
        }

        if ($request->filled('diameter')) {
            $query->byDiameter($request->diameter);
        }

        if ($request->filled('status')) {
            $query->where('status_line', $request->status);
        }

        $lineNumbers = $query->latest()->paginate(15);
        $clusters = JalurCluster::active()->get();

        return view('jalur.line_numbers.index', compact('lineNumbers', 'clusters'));
    }

    public function create()
    {
        $clusters = JalurCluster::active()->get();
        
        return view('jalur.line_numbers.create', compact('clusters'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cluster_id' => 'required|exists:jalur_clusters,id',
            'diameter' => 'required|in:63,90,110,160,180,200',
            'nomor_line' => 'required|string|size:3|regex:/^[0-9]{3}$/',
            'nama_jalan' => 'required|string|max:255',
            'estimasi_panjang' => 'required|numeric|min:0.01',
            'keterangan' => 'nullable|string|max:1000',
        ]);

        $cluster = JalurCluster::findOrFail($validated['cluster_id']);
        
        // Generate line number: diameter-clustercode-LNnomor
        $lineNumber = $validated['diameter'] . '-' . $cluster->code_cluster . '-LN' . $validated['nomor_line'];

        // Check if line number already exists
        if (JalurLineNumber::where('line_number', $lineNumber)->exists()) {
            return back()
                ->withInput()
                ->withErrors(['nomor_line' => 'Line number ' . $lineNumber . ' sudah ada.']);
        }

        // Create line number record
        JalurLineNumber::create([
            'cluster_id' => $validated['cluster_id'],
            'line_number' => $lineNumber,
            'diameter' => $validated['diameter'],
            'nama_jalan' => $validated['nama_jalan'],
            'line_code' => 'LN' . $validated['nomor_line'], // Store the LN code
            'estimasi_panjang' => $validated['estimasi_panjang'],
            'keterangan' => $validated['keterangan'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('jalur.line-numbers.index')
            ->with('success', 'Line number ' . $lineNumber . ' berhasil dibuat.');
    }

    public function show(JalurLineNumber $lineNumber)
    {
        $lineNumber->load([
            'cluster',
            'loweringData.tracerApprover',
            'loweringData.cgpApprover',
            'createdBy'
        ]);

        $stats = [
            'total_lowering' => $lineNumber->loweringData ? $lineNumber->loweringData->count() : 0,
            'completed_lowering' => $lineNumber->loweringData ? $lineNumber->loweringData->where('status_laporan', 'acc_cgp')->count() : 0,
            'progress_percentage' => $lineNumber->getProgressPercentage(),
            'variance_from_estimate' => $lineNumber->getVarianceFromEstimate(),
            'variance_percentage' => $lineNumber->getVariancePercentage(),
        ];

        return view('jalur.line_numbers.show', compact('lineNumber', 'stats'));
    }

    public function edit(JalurLineNumber $lineNumber)
    {
        $lineNumber->load([
            'cluster',
            'loweringData',
            'createdBy'
        ]);
        
        $clusters = JalurCluster::active()->get();
        
        return view('jalur.line_numbers.edit', compact('lineNumber', 'clusters'));
    }

    public function update(Request $request, JalurLineNumber $lineNumber)
    {
        $validated = $request->validate([
            'estimasi_panjang' => 'required|numeric|min:0.01',
            'actual_mc100' => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();

        $lineNumber->update($validated);
        $lineNumber->updateStatus();

        return redirect()
            ->route('jalur.line-numbers.show', $lineNumber)
            ->with('success', 'Line number berhasil diperbarui.');
    }

    public function destroy(JalurLineNumber $lineNumber)
    {
        // Check if line number has lowering data
        if ($lineNumber->loweringData()->count() > 0) {
            return back()->with('error', 'Tidak dapat menghapus line number yang sudah memiliki data lowering.');
        }

        $lineNumber->delete();

        return redirect()
            ->route('jalur.line-numbers.index')
            ->with('success', 'Line number berhasil dihapus.');
    }

    public function updateMC100(Request $request, JalurLineNumber $lineNumber)
    {
        $validated = $request->validate([
            'actual_mc100' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        if (!$lineNumber->canUpdateMC100()) {
            return back()->with('error', 'MC-100 hanya dapat diupdate jika sudah ada data lowering.');
        }

        $lineNumber->update([
            'actual_mc100' => $validated['actual_mc100'],
            'keterangan' => $validated['notes'] ?? $lineNumber->keterangan,
            'updated_by' => auth()->id(),
        ]);

        $lineNumber->updateStatus();

        return back()->with('success', 'Penggelaran MC-100 berhasil diperbarui.');
    }

    public function toggleStatus(JalurLineNumber $lineNumber)
    {
        $lineNumber->update([
            'is_active' => !$lineNumber->is_active,
            'updated_by' => auth()->id(),
        ]);

        $status = $lineNumber->is_active ? 'diaktifkan' : 'dinonaktifkan';
        
        return back()->with('success', "Line number berhasil {$status}.");
    }

    // API endpoints
    public function apiIndex(Request $request)
    {
        $query = JalurLineNumber::query()->with('cluster');

        if ($request->filled('cluster_id')) {
            $query->byCluster($request->cluster_id);
        }

        if ($request->filled('diameter')) {
            $query->byDiameter($request->diameter);
        }

        if ($request->filled('active_only')) {
            $query->active();
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $lineNumbers = $query->select([
                'id', 'line_number', 'diameter', 'status_line', 
                'estimasi_panjang', 'actual_mc100', 'cluster_id'
            ])
            ->orderBy('line_number')
            ->get();

        return response()->json($lineNumbers);
    }

    public function getStats(JalurLineNumber $lineNumber)
    {
        $stats = [
            'line_number' => $lineNumber->line_number,
            'cluster' => $lineNumber->cluster->nama_cluster,
            'diameter' => $lineNumber->diameter,
            'estimasi_panjang' => $lineNumber->estimasi_panjang,
            'total_penggelaran' => $lineNumber->total_penggelaran,
            'actual_mc100' => $lineNumber->actual_mc100,
            'status_line' => $lineNumber->status_line,
            'progress_percentage' => $lineNumber->getProgressPercentage(),
            'variance_from_estimate' => $lineNumber->getVarianceFromEstimate(),
            'variance_percentage' => $lineNumber->getVariancePercentage(),
            'total_lowering_entries' => $lineNumber->loweringData()->count(),
            'completed_lowering_entries' => $lineNumber->loweringData()->where('status_laporan', 'acc_cgp')->count(),
        ];

        return response()->json($stats);
    }
}