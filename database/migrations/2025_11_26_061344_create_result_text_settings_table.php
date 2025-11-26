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
        // Create original table structure (will be renamed in later migration)
        Schema::create('result_text_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_rule_text_id')->constrained('result_rule_texts')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->string('image')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('result_text_settings');
    }
};
