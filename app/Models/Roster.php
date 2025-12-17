<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roster extends Model
{
    protected $fillable = [
        'file_name',
        'storage_path',
        'headers',
        'mapping',
    ];

    protected $casts = [
        'headers' => 'array',
        'mapping' => 'array',
    ];
}
