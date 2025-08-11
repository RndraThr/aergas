<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'type', 'title', 'message', 'priority', 'data',
        'is_read', 'read_at',
    ];


    protected $casts = [
        'data'    => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }

    public function scopeUnread($q) { return $q->where('is_read', false); }
    public function scopeDueSoon($q) { return $q->whereNotNull('sla_due_at')->where('sla_due_at','<=',now()->addDay()); }

    public function markAsRead(): void
    {
        $this->forceFill(['is_read'=>true,'read_at'=>now()])->save();
    }
    // Scopes
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
