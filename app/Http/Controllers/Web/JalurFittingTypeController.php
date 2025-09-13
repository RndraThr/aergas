<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\JalurFittingType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JalurFittingTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = JalurFittingType::query()->with(['createdBy', 'updatedBy']);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $fittingTypes = $query->latest()->paginate(15);

        return view('jalur.fitting-types.index', compact('fittingTypes'));
    }

    public function create()
    {
        return view('jalur.fitting-types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_fitting' => 'required|string|max:100|unique:jalur_fitting_types,nama_fitting',
            'code_fitting' => 'required|string|max:10|unique:jalur_fitting_types,code_fitting',
            'deskripsi' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ], [
            'nama_fitting.unique' => 'Nama fitting sudah digunakan.',
            'code_fitting.unique' => 'Code fitting sudah digunakan.',
        ]);

        $validated['code_fitting'] = strtoupper($validated['code_fitting']);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();
        $validated['is_active'] = $request->boolean('is_active', true);

        try {
            $fittingType = JalurFittingType::create($validated);

            return redirect()
                ->route('jalur.fitting-types.show', $fittingType)
                ->with('success', 'Tipe fitting berhasil dibuat.');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal menyimpan tipe fitting: ' . $e->getMessage());
        }
    }

    public function show(JalurFittingType $fittingType)
    {
        $fittingType->load(['createdBy', 'updatedBy', 'jointData']);

        // Get joint statistics
        $stats = [
            'total_joints' => $fittingType->jointData()->count(),
            'active_joints' => $fittingType->jointData()->whereIn('status_laporan', ['draft', 'acc_tracer'])->count(),
            'completed_joints' => $fittingType->jointData()->where('status_laporan', 'acc_cgp')->count(),
            'recent_joints' => $fittingType->jointData()->latest()->limit(5)->get(),
        ];

        return view('jalur.fitting-types.show', compact('fittingType', 'stats'));
    }

    public function edit(JalurFittingType $fittingType)
    {
        return view('jalur.fitting-types.edit', compact('fittingType'));
    }

    public function update(Request $request, JalurFittingType $fittingType)
    {
        $validated = $request->validate([
            'nama_fitting' => 'required|string|max:100|unique:jalur_fitting_types,nama_fitting,' . $fittingType->id,
            'code_fitting' => 'required|string|max:10|unique:jalur_fitting_types,code_fitting,' . $fittingType->id,
            'deskripsi' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ], [
            'nama_fitting.unique' => 'Nama fitting sudah digunakan.',
            'code_fitting.unique' => 'Code fitting sudah digunakan.',
        ]);

        $validated['code_fitting'] = strtoupper($validated['code_fitting']);
        $validated['updated_by'] = auth()->id();
        $validated['is_active'] = $request->boolean('is_active', true);

        try {
            $fittingType->update($validated);

            return redirect()
                ->route('jalur.fitting-types.show', $fittingType)
                ->with('success', 'Tipe fitting berhasil diperbarui.');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui tipe fitting: ' . $e->getMessage());
        }
    }

    public function destroy(JalurFittingType $fittingType)
    {
        // Check if fitting type has been used in joints
        if ($fittingType->jointData()->exists()) {
            return back()->with('error', 'Tipe fitting tidak dapat dihapus karena sudah digunakan dalam data joint.');
        }

        try {
            $fittingType->delete();

            return redirect()
                ->route('jalur.fitting-types.index')
                ->with('success', 'Tipe fitting berhasil dihapus.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus tipe fitting: ' . $e->getMessage());
        }
    }

    public function toggleStatus(JalurFittingType $fittingType)
    {
        try {
            $fittingType->update([
                'is_active' => !$fittingType->is_active,
                'updated_by' => auth()->id(),
            ]);

            $status = $fittingType->is_active ? 'diaktifkan' : 'dinonaktifkan';

            return back()->with('success', "Tipe fitting berhasil {$status}.");

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengubah status tipe fitting: ' . $e->getMessage());
        }
    }
}