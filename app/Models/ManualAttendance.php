<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualAttendance extends Model
{
    protected $fillable = [
        'roll_number',
        'punch_date',
        'punch_time',
        'state',
        'marked_by',
        'is_manual',
        'notes',
    ];

    protected $casts = [
        'punch_date' => 'date',
        'punch_time' => 'string',
        'is_manual' => 'boolean',
    ];

    /**
     * Get the student who this attendance belongs to
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'roll_number', 'roll_number');
    }

    /**
     * Get the user who marked this attendance
     */
    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
