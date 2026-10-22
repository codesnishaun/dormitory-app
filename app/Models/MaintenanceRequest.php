<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRequest extends Model
{
    public const CATEGORIES = [
        'Plumbing',
        'Electrical',
        'Appliance',
        'Carpentry',
        'Internet',
        'Cleaning',
        'Other',
    ];

    public const PRIORITIES = ['High', 'Medium', 'Low'];

    public const STATUSES = ['Pending', 'In Progress', 'Resolved'];

    protected $fillable = [
        'user_id',
        'room_assignment_id',
        'title',
        'category',
        'priority',
        'description',
        'status',
        'admin_remarks',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function roomAssignment(): BelongsTo
    {
        return $this->belongsTo(RoomAssignment::class);
    }

    // Display ID such as MR-0012
    public function getReferenceAttribute(): string
    {
        return 'MR-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }
}