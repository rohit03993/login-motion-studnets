<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all batches for this course
     */
    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    /**
     * Get active batches only
     */
    public function activeBatches(): HasMany
    {
        return $this->hasMany(Batch::class)->where('is_active', true);
    }

    /**
     * Get students linked to this course (by class_course string match)
     */
    public function students()
    {
        return Student::where('class_course', $this->name);
    }

    /**
     * Get batch count for this course
     */
    public function getBatchCountAttribute(): int
    {
        return $this->batches()->count();
    }

    /**
     * Get student count for this course
     */
    public function getStudentCountAttribute(): int
    {
        return $this->students()->count();
    }

    /**
     * Check if course can be deleted
     */
    public function isDeletable(): bool
    {
        // Allow delete if no students; batches (if any) will be removed with the course
        return $this->student_count === 0;
    }
}
