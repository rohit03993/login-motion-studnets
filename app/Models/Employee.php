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
        'discontinued_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'discontinued_at' => 'datetime',
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

    /**
     * Check if employee is active (not discontinued)
     */
    public function isActiveEmployee(): bool
    {
        return $this->is_active && $this->discontinued_at === null;
    }

    /**
     * Discontinue employee (deactivate with timestamp)
     */
    public function discontinue(): bool
    {
        $this->is_active = false;
        $this->discontinued_at = now();
        return $this->save();
    }

    /**
     * Restore discontinued employee
     */
    public function restore(): bool
    {
        $this->is_active = true;
        $this->discontinued_at = null;
        return $this->save();
    }
}
