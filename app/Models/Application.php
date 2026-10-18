<?php

namespace App\Models;

use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'applicant_name', 'email', 'phone', 'message',
        'preferred_room_id', 'move_in_date',
    ];

    public function preferredRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'preferred_room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
