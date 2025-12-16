<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Check if columns don't exist before adding (safer approach)
            if (!Schema::hasColumn('students', 'parent_phone_secondary')) {
                $table->string('parent_phone_secondary', 20)->nullable();
            }
            if (!Schema::hasColumn('students', 'whatsapp_send_to')) {
                $table->enum('whatsapp_send_to', ['primary', 'secondary', 'both'])->default('primary');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['parent_phone_secondary', 'whatsapp_send_to']);
        });
    }
};

