<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    protected $fillable = [
        'roll_number',
        'name',
        'father_name',
        'mobile',
        'category',
        'is_active',
        'user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user account associated with this employee
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if employee has login credentials
     */
    public function hasLogin(): bool
    {
        return $this->user_id !== null && $this->user !== null;
    }
}
