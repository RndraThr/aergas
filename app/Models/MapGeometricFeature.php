<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MapGeometricFeature extends Model
{
    use HasFactory;

    protected $table = 'map_geometric_features';

    protected $fillable = [
        'name',
        'feature_type',
        'line_number_id',
        'cluster_id',
        'geometry',
        'style_properties',
        'metadata',
        'is_visible',
        'display_order',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'geometry' => 'array',
        'style_properties' => 'array',
        'metadata' => 'array',
        'is_visible' => 'boolean',
        'display_order' => 'integer'
    ];

    // Relationships
    public function lineNumber(): BelongsTo
    {
        return $this->belongsTo(JalurLineNumber::class, 'line_number_id');
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(JalurCluster::class, 'cluster_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('feature_type', $type);
    }

    public function scopeByLineNumber($query, int $lineNumberId)
    {
        return $query->where('line_number_id', $lineNumberId);
    }

    public function scopeByCluster($query, int $clusterId)
    {
        return $query->where('cluster_id', $clusterId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('created_at');
    }

    // Helper methods
    public function generateNameFromLineNumber(): string
    {
        if ($this->lineNumber) {
            $clusterName = $this->lineNumber->cluster ? $this->lineNumber->cluster->nama_cluster : 'Unknown Cluster';
            return "Line {$this->lineNumber->line_number} - {$clusterName}";
        }
        return $this->name;
    }

    public function getDefaultStyleProperties(): array
    {
        $defaults = [
            'line' => [
                'color' => '#3388ff',
                'weight' => 4,
                'opacity' => 0.8
            ],
            'polygon' => [
                'color' => '#ff7800',
                'weight' => 3,
                'opacity' => 0.8,
                'fillColor' => '#ff7800',
                'fillOpacity' => 0.3
            ],
            'circle' => [
                'color' => '#ff3388',
                'weight' => 3,
                'opacity' => 0.8,
                'fillColor' => '#ff3388',
                'fillOpacity' => 0.2
            ]
        ];

        return $defaults[$this->feature_type] ?? $defaults['line'];
    }

    public function getStylePropertiesAttribute($value)
    {
        $decoded = $value ? json_decode($value, true) : [];
        return array_merge($this->getDefaultStyleProperties(), $decoded ?: []);
    }

    public function toGeoJson(): array
    {
        return [
            'type' => 'Feature',
            'id' => $this->id,
            'properties' => [
                'name' => $this->name,
                'feature_type' => $this->feature_type,
                'line_number_id' => $this->line_number_id,
                'cluster_id' => $this->cluster_id,
                'style' => $this->style_properties,
                'metadata' => $this->metadata,
                'is_visible' => $this->is_visible,
                'display_order' => $this->display_order
            ],
            'geometry' => $this->geometry
        ];
    }

    // Boot method to handle auto-naming
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->name && $model->line_number_id) {
                $model->name = $model->generateNameFromLineNumber();
            }

            if (!$model->style_properties) {
                $model->style_properties = $model->getDefaultStyleProperties();
            }
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }
}