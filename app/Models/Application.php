<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    /** @use HasFactory<\Database\Factories\ApplicationFactory> */
    use HasFactory;

        protected $fillable = [
        'applicant_name', 'email', 'phone', 'message',
        'preferred_room_id', 'move_in_date',
    ];
}