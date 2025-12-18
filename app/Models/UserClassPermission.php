<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserClassPermission extends Model
{
    protected $fillable = [
        'user_id',
        'class_name',
        'can_mark',
    ];

    protected $casts = [
        'can_mark' => 'boolean',
    ];
}
