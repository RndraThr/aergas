<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MapGeometricFeature;
use App\Models\JalurLineNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use ZipArchive;
use SimpleXMLElement;

class JalurKmzImportController extends Controller
{
    /**
     * Show import page
     */
    public function index()
    {
        $unassignedFeatures = MapGeometricFeature::whereNull('line_number_id')
            ->where('feature_type', 'line')
            ->orderBy('created_at', 'desc')
            ->get();

        $lineNumbers = JalurLineNumber::with('cluster')
            ->active()
            ->orderBy('line_number')
            ->get();

        return view('jalur.kmz-import', compact('unassignedFeatures', 'lineNumbers'));
    }

    /**
     * Handle KMZ/KML file upload and parse
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // max 10MB
        ]);

        try {
            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());

            // Validate extension manually
            if (!in_array($extension, ['kmz', 'kml'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format file tidak valid. Hanya file KMZ dan KML yang diperbolehkan.'
                ], 422);
            }

            if ($extension === 'kmz') {
                $kmlContent = $this->extractKmlFromKmz($file);
            } else {
                $kmlContent = file_get_contents($file->getRealPath());
            }

            $features = $this->parseKml($kmlContent);

            // Save features to database
            $savedFeatures = [];
            foreach ($features as $feature) {
                $mapFeature = MapGeometricFeature::create([
                    'name' => $feature['name'],
                    'feature_type' => 'line',
                    'line_number_id' => null, // Unassigned
                    'cluster_id' => null,
                    'geometry' => [
                        'type' => $feature['geometry']['type'],
                        'coordinates' => $feature['geometry']['coordinates']
                    ],
                    'style_properties' => $feature['style'] ?? [
                        'color' => '#3388ff',
                        'weight' => 3,
                        'opacity' => 0.8
                    ],
                    'metadata' => [
                        'source' => 'kmz_import',
                        'original_filename' => $file->getClientOriginalName(),
                        'description' => $feature['description'] ?? null,
                        'imported_at' => now()->toIso8601String()
                    ],
                    'is_visible' => true,
                    'created_by' => auth()->id()
                ]);

                $savedFeatures[] = $mapFeature;
            }

            return response()->json([
                'success' => true,
                'message' => count($savedFeatures) . ' jalur berhasil diimport',
                'features' => $savedFeatures,
                'count' => count($savedFeatures)
            ]);

        } catch (\Exception $e) {
            Log::error('KMZ Import Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimport file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign imported feature to Line Number
     */
    public function assign(Request $request, MapGeometricFeature $feature)
    {
        $request->validate([
            'line_number_id' => 'required|exists:jalur_line_numbers,id'
        ]);

        try {
            $lineNumber = JalurLineNumber::with('cluster')->findOrFail($request->line_number_id);

            // Update feature with line number assignment

            // Calculate style based on diameter
            $diameterColors = [
                '63' => '#3B82F6',   // Blue
                '90' => '#F59E0B',   // Orange
                '180' => '#EF4444'   // Red
            ];

            $diameterWeights = [
                '63' => 3,
                '90' => 4,
                '180' => 5
            ];

            $diameter = $lineNumber->diameter;
            $color = $diameterColors[$diameter] ?? '#3B82F6';
            $weight = $diameterWeights[$diameter] ?? 3;

            $feature->update([
                'line_number_id' => $lineNumber->id,
                'cluster_id' => $lineNumber->cluster_id,
                'name' => $lineNumber->line_number . ' - ' . $lineNumber->nama_jalan,
                'style_properties' => [
                    'color' => $color,
                    'weight' => $weight,
                    'opacity' => 0.8,
                    'dashArray' => null
                ],
                'metadata' => array_merge($feature->metadata ?? [], [
                    'line_number' => $lineNumber->line_number,
                    'nama_jalan' => $lineNumber->nama_jalan,
                    'diameter' => $lineNumber->diameter,
                    'cluster_name' => $lineNumber->cluster->nama_cluster ?? null,
                    'cluster_code' => $lineNumber->cluster->code_cluster ?? null,
                    'line_code' => $lineNumber->line_code,
                    'estimasi_panjang' => $lineNumber->estimasi_panjang,
                    'total_penggelaran' => $lineNumber->total_penggelaran,
                    'actual_mc100' => $lineNumber->actual_mc100,
                    'status' => $lineNumber->status_line,
                    'keterangan' => $lineNumber->keterangan,
                    'assigned_at' => now()->toIso8601String(),
                    'assigned_by' => auth()->user()->name
                ]),
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jalur berhasil di-assign ke ' . $lineNumber->line_code,
                'feature' => $feature->fresh(['lineNumber.cluster'])
            ]);

        } catch (\Exception $e) {
            Log::error('Feature Assignment Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal assign jalur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete unassigned feature
     */
    public function destroy(MapGeometricFeature $feature)
    {
        try {
            // Only allow deletion of unassigned features
            if ($feature->line_number_id !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus jalur yang sudah di-assign'
                ], 403);
            }

            $feature->delete();

            return response()->json([
                'success' => true,
                'message' => 'Jalur berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus jalur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete imported jalur features
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'mode' => 'required|in:all,unassigned,assigned,selected',
            'feature_ids' => 'required_if:mode,selected|array',
            'feature_ids.*' => 'exists:map_geometric_features,id'
        ]);

        try {
            $mode = $request->mode;
            $deleted = 0;

            DB::beginTransaction();

            if ($mode === 'all') {
                // Delete all jalur features (features with line_number_id or cluster_id)
                $deleted = MapGeometricFeature::where(function ($q) {
                    $q->whereNotNull('line_number_id')
                        ->orWhereNotNull('cluster_id');
                })->delete();

            } elseif ($mode === 'unassigned') {
                // Delete only unassigned jalur features
                $deleted = MapGeometricFeature::whereNull('line_number_id')
                    ->where('feature_type', 'line')
                    ->delete();

            } elseif ($mode === 'assigned') {
                // Delete only assigned jalur features
                $deleted = MapGeometricFeature::whereNotNull('line_number_id')
                    ->delete();

            } elseif ($mode === 'selected') {
                // Delete selected features
                $deleted = MapGeometricFeature::whereIn('id', $request->feature_ids)
                    ->delete();
            }

            DB::commit();

            Log::info('Bulk delete jalur features', [
                'mode' => $mode,
                'deleted_count' => $deleted,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$deleted} jalur berhasil dihapus",
                'deleted_count' => $deleted
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk Delete Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus jalur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract KML from KMZ file
     */
    private function extractKmlFromKmz($file): string
    {
        $zip = new ZipArchive;
        $tempPath = $file->getRealPath();

        if ($zip->open($tempPath) === TRUE) {
            // KMZ files typically contain a doc.kml file
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (pathinfo($filename, PATHINFO_EXTENSION) === 'kml') {
                    $kmlContent = $zip->getFromIndex($i);
                    $zip->close();
                    return $kmlContent;
                }
            }
            $zip->close();
            throw new \Exception('No KML file found in KMZ archive');
        }

        throw new \Exception('Failed to open KMZ file');
    }

    /**
     * Parse KML content and extract LineString features
     */
    private function parseKml(string $kmlContent): array
    {
        $features = [];

        // Remove XML namespaces for easier parsing
        $kmlContent = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $kmlContent);

        $xml = new SimpleXMLElement($kmlContent);

        // Parse Placemarks (main feature container in KML)
        $placemarks = $xml->xpath('//Placemark');

        foreach ($placemarks as $placemark) {
            // Get feature name
            $name = (string) ($placemark->name ?? 'Unnamed Line');
            $description = (string) ($placemark->description ?? '');

            // Look for LineString geometry
            $lineString = $placemark->xpath('.//LineString/coordinates');

            if (!empty($lineString)) {
                $coordinatesText = trim((string) $lineString[0]);
                $coordinates = $this->parseCoordinates($coordinatesText);

                if (!empty($coordinates)) {
                    // Parse style if available
                    $style = $this->parseStyle($placemark);

                    $features[] = [
                        'name' => $name,
                        'description' => $description,
                        'geometry' => [
                            'type' => 'LineString',
                            'coordinates' => $coordinates
                        ],
                        'style' => $style
                    ];
                }
            }

            // Also check for MultiGeometry containing LineStrings
            $multiGeometry = $placemark->xpath('.//MultiGeometry');
            if (!empty($multiGeometry)) {
                $lineStrings = $multiGeometry[0]->xpath('.//LineString/coordinates');
                foreach ($lineStrings as $ls) {
                    $coordinatesText = trim((string) $ls);
                    $coordinates = $this->parseCoordinates($coordinatesText);

                    if (!empty($coordinates)) {
                        $style = $this->parseStyle($placemark);

                        $features[] = [
                            'name' => $name . ' (Part)',
                            'description' => $description,
                            'geometry' => [
                                'type' => 'LineString',
                                'coordinates' => $coordinates
                            ],
                            'style' => $style
                        ];
                    }
                }
            }
        }

        return $features;
    }

    /**
     * Parse coordinate string to array of [lng, lat] pairs
     */
    private function parseCoordinates(string $coordinatesText): array
    {
        $coordinates = [];
        $points = preg_split('/[\s\n\r]+/', trim($coordinatesText));

        foreach ($points as $point) {
            $point = trim($point);
            if (empty($point))
                continue;

            // KML format: longitude,latitude,altitude
            $parts = explode(',', $point);
            if (count($parts) >= 2) {
                // Convert to [lng, lat] format (GeoJSON format)
                $lng = (float) trim($parts[0]);
                $lat = (float) trim($parts[1]);

                // Validate coordinates
                if ($lng >= -180 && $lng <= 180 && $lat >= -90 && $lat <= 90) {
                    $coordinates[] = [$lng, $lat];
                }
            }
        }

        return $coordinates;
    }

    /**
     * Parse style information from KML
     */
    private function parseStyle($placemark): array
    {
        $style = [
            'color' => '#3388ff',
            'weight' => 3,
            'opacity' => 0.8
        ];

        // Try to get style from styleUrl reference
        $styleUrl = (string) ($placemark->styleUrl ?? '');

        // Try to get inline style
        $lineStyle = $placemark->xpath('.//LineStyle');
        if (!empty($lineStyle)) {
            $color = (string) ($lineStyle[0]->color ?? '');
            $width = (float) ($lineStyle[0]->width ?? 3);

            if ($color) {
                // KML color format: aabbggrr (alpha, blue, green, red)
                // Convert to hex #rrggbb
                if (strlen($color) === 8) {
                    $r = substr($color, 6, 2);
                    $g = substr($color, 4, 2);
                    $b = substr($color, 2, 2);
                    $style['color'] = '#' . $r . $g . $b;
                }
            }

            $style['weight'] = $width;
        }

        return $style;
    }
}
