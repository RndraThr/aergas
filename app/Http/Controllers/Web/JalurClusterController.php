<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\JalurCluster;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JalurClusterController extends Controller
{

    public function index(Request $request)
    {
        $query = JalurCluster::query()->with(['lineNumbers', 'createdBy']);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        $clusters = $query->latest()->paginate(15);

        return view('jalur.clusters.index', compact('clusters'));
    }

    public function create()
    {
        return view('jalur.clusters.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_cluster' => 'required|string|max:255',
            'code_cluster' => [
                'required',
                'string',
                'max:10',
                'uppercase',
                Rule::unique('jalur_clusters', 'code_cluster')
            ],
            'deskripsi' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        JalurCluster::create($validated);

        return redirect()
            ->route('jalur.clusters.index')
            ->with('success', 'Cluster berhasil dibuat.');
    }

    public function show(JalurCluster $cluster)
    {
        $cluster->load([
            'lineNumbers.loweringData',
            'createdBy'
        ]);

        // Calculate statistics
        $totalLowering = $cluster->lineNumbers->sum(fn($line) => $line->loweringData->count());
        $totalJoint = $cluster->lineNumbers->sum(fn($line) => $line->jointData->count());
        $totalEstimate = $cluster->lineNumbers->sum('estimasi_panjang');
        $totalPenggelaran = $cluster->lineNumbers->sum('total_penggelaran');
        $totalActual = $cluster->lineNumbers->whereNotNull('actual_mc100')->sum('actual_mc100');

        $stats = [
            'total_line_numbers' => $cluster->lineNumbers->count(),
            'total_lowering' => $totalLowering,
            'total_joint' => $totalJoint,
            'total_estimate' => $totalEstimate,
            'total_penggelaran' => $totalPenggelaran,
            'total_actual' => $totalActual,
            'total_variance' => $totalActual - $totalEstimate,
        ];

        return view('jalur.clusters.show', compact('cluster', 'stats'));
    }

    public function edit(JalurCluster $cluster)
    {
        return view('jalur.clusters.edit', compact('cluster'));
    }

    public function update(Request $request, JalurCluster $cluster)
    {
        $validated = $request->validate([
            'nama_cluster' => 'required|string|max:255',
            'code_cluster' => [
                'required',
                'string',
                'max:10',
                'uppercase',
                Rule::unique('jalur_clusters', 'code_cluster')->ignore($cluster->id)
            ],
            'deskripsi' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();

        $cluster->update($validated);

        return redirect()
            ->route('jalur.clusters.show', $cluster)
            ->with('success', 'Cluster berhasil diperbarui.');
    }

    public function destroy(JalurCluster $cluster)
    {
        // Check if cluster has line numbers
        if ($cluster->lineNumbers()->count() > 0) {
            return back()->with('error', 'Tidak dapat menghapus cluster yang masih memiliki line number.');
        }

        $cluster->delete();

        return redirect()
            ->route('jalur.clusters.index')
            ->with('success', 'Cluster berhasil dihapus.');
    }

    public function toggleStatus(JalurCluster $cluster)
    {
        $cluster->update([
            'is_active' => !$cluster->is_active,
            'updated_by' => auth()->id(),
        ]);

        $status = $cluster->is_active ? 'diaktifkan' : 'dinonaktifkan';
        
        return back()->with('success', "Cluster berhasil {$status}.");
    }

    // API endpoints
    public function apiIndex(Request $request)
    {
        $query = JalurCluster::query();

        if ($request->filled('active_only')) {
            $query->active();
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $clusters = $query->select(['id', 'nama_cluster', 'code_cluster'])
                         ->orderBy('nama_cluster')
                         ->get();

        return response()->json($clusters);
    }

    public function getLineNumbers(JalurCluster $cluster, Request $request)
    {
        $query = $cluster->lineNumbers();

        if ($request->filled('diameter')) {
            $query->byDiameter($request->diameter);
        }

        if ($request->filled('status')) {
            $query->where('status_line', $request->status);
        }

        $lineNumbers = $query->select(['id', 'line_number', 'diameter', 'status_line', 'estimasi_panjang', 'actual_mc100'])
                            ->orderBy('line_number')
                            ->get();

        return response()->json($lineNumbers);
    }
}