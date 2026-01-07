<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MapGeometricFeature;
use App\Models\JalurLineNumber;
use App\Models\JalurCluster;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
class MapFeatureController extends Controller
{
    public function __construct()
    {
        // Controller middleware will be applied via routes
    }

    /**
     * Get all map features for display
     */
    public function index(): JsonResponse
    {
        try {
            // Get context parameter (default: 'main' for main dashboard)
            $context = request()->get('context', 'main');

            // Simple test first - just return empty array if model not working
            try {
                $query = MapGeometricFeature::with(['lineNumber.cluster', 'cluster', 'creator'])
                    ->visible()
                    ->ordered();

                // Filter based on context
                if ($context === 'main') {
                    // Main dashboard: exclude jalur-related features (no line_number_id and no cluster_id)
                    $query->whereNull('line_number_id')
                        ->whereNull('cluster_id');
                } elseif ($context === 'jalur') {
                    // Jalur dashboard: only jalur-related features (has line_number_id or cluster_id)
                    $query->where(function ($q) {
                        $q->whereNotNull('line_number_id')
                            ->orWhereNotNull('cluster_id');
                    });
                }
                // If context is 'all', no filter applied

                $features = $query->get();

                $geoJsonFeatures = $features->map(function ($feature) {
                    return $feature->toGeoJson();
                });
            } catch (\Exception $modelError) {
                // If model fails, return empty array
                Log::error('MapGeometricFeature model error: ' . $modelError->getMessage());
                return response()->json([
                    'success' => true,
                    'features' => [],
                    'count' => 0,
                    'note' => 'Model error - returning empty features'
                ]);
            }

            return response()->json([
                'success' => true,
                'features' => $geoJsonFeatures,
                'count' => $features->count(),
                'context' => $context
            ]);
        } catch (\Exception $e) {
            Log::error('MapFeatureController index error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load map features',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Store a new map feature
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'feature_type' => ['required', Rule::in(['line', 'polygon', 'circle'])],
                'line_number_id' => 'nullable|exists:jalur_line_numbers,id',
                'cluster_id' => 'nullable|exists:jalur_clusters,id',
                'geometry' => 'required|array',
                'geometry.type' => 'required|string',
                'geometry.coordinates' => 'required|array',
                'style_properties' => 'nullable|array',
                'metadata' => 'nullable|array',
                'is_visible' => 'sometimes|boolean',
                'display_order' => 'sometimes|integer|min:0'
            ]);

            // Auto-generate name if not provided and line_number_id exists
            if (!$validated['name'] && !empty($validated['line_number_id'])) {
                $lineNumber = JalurLineNumber::with('cluster')->find($validated['line_number_id']);
                if ($lineNumber) {
                    $clusterName = $lineNumber->cluster ? $lineNumber->cluster->nama_cluster : 'Unknown Cluster';
                    $validated['name'] = "Line {$lineNumber->line_number} - {$clusterName}";
                }
            }

            // Set default name if still empty
            if (!$validated['name']) {
                $validated['name'] = ucfirst($validated['feature_type']) . ' Feature';
            }

            // Set created_by
            $validated['created_by'] = Auth::id();

            $feature = MapGeometricFeature::create($validated);
            $feature->load(['lineNumber.cluster', 'cluster', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Map feature created successfully',
                'feature' => $feature->toGeoJson()
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create map feature',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing map feature
     */
    public function update(Request $request, MapGeometricFeature $feature): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'feature_type' => ['sometimes', Rule::in(['line', 'polygon', 'circle'])],
                'line_number_id' => 'sometimes|nullable|exists:jalur_line_numbers,id',
                'cluster_id' => 'sometimes|nullable|exists:jalur_clusters,id',
                'geometry' => 'sometimes|array',
                'geometry.type' => 'required_with:geometry|string',
                'geometry.coordinates' => 'required_with:geometry|array',
                'style_properties' => 'sometimes|nullable|array',
                'metadata' => 'sometimes|nullable|array',
                'is_visible' => 'sometimes|boolean',
                'display_order' => 'sometimes|integer|min:0'
            ]);

            $feature->update($validated);
            $feature->load(['lineNumber.cluster', 'cluster', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Map feature updated successfully',
                'feature' => $feature->toGeoJson()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update map feature',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a map feature
     */
    public function destroy(MapGeometricFeature $feature): JsonResponse
    {
        try {
            $feature->delete();

            return response()->json([
                'success' => true,
                'message' => 'Map feature deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete map feature',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle visibility of a map feature
     */
    public function toggleVisibility(MapGeometricFeature $feature): JsonResponse
    {
        try {
            $feature->update([
                'is_visible' => !$feature->is_visible,
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Feature visibility updated',
                'is_visible' => $feature->is_visible
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle visibility',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get line numbers for dropdown
     */
    public function getLineNumbers(): JsonResponse
    {
        try {
            // Test if JalurLineNumber model exists
            try {
                $lineNumbers = JalurLineNumber::with('cluster')
                    ->active()
                    ->get()
                    ->map(function ($lineNumber) {
                        return [
                            'id' => $lineNumber->id,
                            'line_number' => $lineNumber->line_number,
                            'nama_jalan' => $lineNumber->nama_jalan,
                            'diameter' => $lineNumber->diameter,
                            'cluster_id' => $lineNumber->cluster_id,
                            'cluster_name' => $lineNumber->cluster ? $lineNumber->cluster->nama_cluster : 'No Cluster',
                            'cluster_code' => $lineNumber->cluster ? $lineNumber->cluster->code_cluster : '-',
                            'line_code' => $lineNumber->line_code,
                            'estimasi_panjang' => $lineNumber->estimasi_panjang,
                            'total_penggelaran' => $lineNumber->total_penggelaran,
                            'actual_mc100' => $lineNumber->actual_mc100,
                            'status_line' => $lineNumber->status_line,
                            'keterangan' => $lineNumber->keterangan,
                            'display_text' => "{$lineNumber->line_number}" .
                                ($lineNumber->cluster ? " - {$lineNumber->cluster->nama_cluster}" : "")
                        ];
                    });
            } catch (\Exception $modelError) {
                Log::error('JalurLineNumber model error: ' . $modelError->getMessage());
                return response()->json([
                    'success' => true,
                    'line_numbers' => [],
                    'note' => 'JalurLineNumber model error - returning empty array'
                ]);
            }

            return response()->json([
                'success' => true,
                'line_numbers' => $lineNumbers
            ]);
        } catch (\Exception $e) {
            Log::error('MapFeatureController getLineNumbers error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load line numbers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get clusters for dropdown
     */
    public function getClusters(): JsonResponse
    {
        try {
            $clusters = JalurCluster::select('id', 'nama_cluster', 'code_cluster')
                ->orderBy('nama_cluster')
                ->get()
                ->map(function ($cluster) {
                    return [
                        'id' => $cluster->id,
                        'name' => $cluster->nama_cluster,
                        'code' => $cluster->code_cluster,
                        'display_text' => "{$cluster->code_cluster} - {$cluster->nama_cluster}"
                    ];
                });

            return response()->json([
                'success' => true,
                'clusters' => $clusters
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load clusters',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
