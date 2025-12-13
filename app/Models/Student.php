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
}

