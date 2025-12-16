<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Batch extends Model
{
    protected $fillable = [
        'name',
        'course_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the course this batch belongs to
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get students linked to this batch (by batch string match)
     */
    public function students()
    {
        return Student::where('batch', $this->name);
    }

    /**
     * Get student count for this batch
     */
    public function getStudentCountAttribute(): int
    {
        return $this->students()->count();
    }

    /**
     * Check if batch can be deleted
     */
    public function isDeletable(): bool
    {
        return $this->student_count === 0;
    }
}
