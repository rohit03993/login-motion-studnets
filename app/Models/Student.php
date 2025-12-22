<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

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
        'parent_phone_secondary',
        'whatsapp_send_to',
        'alerts_enabled',
        'discontinued_at',
    ];

    protected $casts = [
        'alerts_enabled' => 'boolean',
        'discontinued_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Check if student is active (not discontinued)
     */
    public function isActive(): bool
    {
        return $this->deleted_at === null && $this->discontinued_at === null;
    }

    /**
     * Discontinue student (soft delete with timestamp)
     */
    public function discontinue(): bool
    {
        $this->discontinued_at = now();
        return $this->delete(); // Soft delete
    }

    /**
     * Restore discontinued student
     */
    public function restore(): bool
    {
        $this->discontinued_at = null;
        return parent::restore();
    }

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

    /**
     * Get phone numbers to send WhatsApp based on whatsapp_send_to setting
     * Returns array of normalized phone numbers
     */
    public function getWhatsAppPhones(): array
    {
        $phones = [];
        $sendTo = $this->whatsapp_send_to ?? 'primary';

        if ($sendTo === 'primary' || $sendTo === 'both') {
            if (!empty($this->parent_phone)) {
                $phones[] = $this->parent_phone;
            }
        }

        if ($sendTo === 'secondary' || $sendTo === 'both') {
            if (!empty($this->parent_phone_secondary)) {
                $phones[] = $this->parent_phone_secondary;
            }
        }

        return array_filter($phones); // Remove any empty values
    }
}

