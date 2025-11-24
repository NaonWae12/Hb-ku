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
        Schema::create('result_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->onDelete('cascade');
            $table->enum('condition_type', ['range', 'equal', 'greater', 'less'])->default('range');
            $table->integer('min_score')->nullable(); // untuk range
            $table->integer('max_score')->nullable(); // untuk range
            $table->integer('single_score')->nullable(); // untuk equal, greater, less
            $table->integer('order')->default(0);
            $table->timestamps();
        });
        
        Schema::create('result_rule_texts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_rule_id')->constrained()->onDelete('cascade');
            $table->text('result_text');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('result_rule_texts');
        Schema::dropIfExists('result_rules');
    }
};
