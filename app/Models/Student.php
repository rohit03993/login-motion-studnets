<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $primaryKey = 'roll_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'roll_number',
        'name',
        'father_name',
        'class_course',
        'batch',
        'parent_phone',
        'alerts_enabled',
    ];

    protected $casts = [
        'alerts_enabled' => 'boolean',
    ];

    /**
     * Get Course model by matching class_course name
     */
    public function getCourse()
    {
        if (!$this->class_course) {
            return null;
        }
        return Course::where('name', $this->class_course)->first();
    }

    /**
     * Get Batch model by matching batch name
     */
    public function getBatch()
    {
        if (!$this->batch) {
            return null;
        }
        return Batch::where('name', $this->batch)->first();
    }
}

