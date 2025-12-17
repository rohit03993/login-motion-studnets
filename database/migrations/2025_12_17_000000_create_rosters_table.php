<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rosters', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('storage_path');
            $table->json('headers');
            $table->json('mapping');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rosters');
    }
};
