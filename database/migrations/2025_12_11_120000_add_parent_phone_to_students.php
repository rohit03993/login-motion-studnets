<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('parent_phone')->nullable()->after('class_course');
            $table->boolean('alerts_enabled')->default(true)->after('parent_phone');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['parent_phone', 'alerts_enabled']);
        });
    }
};

