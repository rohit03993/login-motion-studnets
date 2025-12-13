<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('roll_number')->index();
            $table->string('state'); // IN or OUT
            $table->date('punch_date');
            $table->time('punch_time');
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->nullable(); // success/failed
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['roll_number', 'punch_date', 'punch_time', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};

