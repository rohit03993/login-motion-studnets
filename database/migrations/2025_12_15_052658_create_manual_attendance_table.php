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
        Schema::create('manual_attendances', function (Blueprint $table) {
            $table->id();
            $table->string('roll_number')->index();
            $table->date('punch_date')->index();
            $table->time('punch_time');
            $table->enum('state', ['IN', 'OUT']);
            $table->unsignedBigInteger('marked_by'); // user_id who marked it
            $table->boolean('is_manual')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for efficient queries
            $table->index(['roll_number', 'punch_date']);
            $table->index(['punch_date', 'state']);
            
            // Foreign key to users table
            $table->foreign('marked_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_attendances');
    }
};
