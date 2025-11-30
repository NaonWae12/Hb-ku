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
        Schema::create('form_headers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->unique()->constrained('forms')->onDelete('cascade');
            $table->string('image_path')->nullable();
            $table->enum('image_mode', ['stretch', 'cover', 'contain', 'repeat', 'center', 'no-repeat'])->default('cover');
            $table->enum('source', ['template', 'upload'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_headers');
    }
};

