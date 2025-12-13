<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppLog extends Model
{
    protected $table = 'whatsapp_logs'; // fix default pluralization

    protected $fillable = [
        'student_id',
        'roll_number',
        'state',
        'punch_date',
        'punch_time',
        'sent_at',
        'status',
        'error',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'punch_date' => 'date',
    ];
}

