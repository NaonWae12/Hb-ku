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
        // Rename table
        Schema::rename('result_text_settings', 'setting_results');
        
        // Add new columns
        Schema::table('setting_results', function (Blueprint $table) {
            $table->foreignId('form_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->string('rule_group_id')->nullable()->after('form_id');
            $table->string('image_alignment')->default('center')->after('image');
            $table->string('text_alignment')->default('center')->after('image_alignment');
            
            // Add index for faster queries
            $table->index(['form_id', 'rule_group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('setting_results', function (Blueprint $table) {
            $table->dropIndex(['form_id', 'rule_group_id']);
            $table->dropForeign(['form_id']);
            $table->dropColumn(['form_id', 'rule_group_id', 'image_alignment', 'text_alignment']);
        });
        
        Schema::rename('setting_results', 'result_text_settings');
    }
};
