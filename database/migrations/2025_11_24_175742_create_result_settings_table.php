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
        Schema::create('result_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->onDelete('cascade');
            $table->foreignId('result_rule_id')->nullable()->constrained('result_rules')->onDelete('cascade');
            $table->string('image')->nullable();
            $table->string('image_alignment')->default('center'); // left, center, right
            $table->text('result_text')->nullable();
            $table->string('text_alignment')->default('center'); // left, center, right
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('result_settings');
    }
};
