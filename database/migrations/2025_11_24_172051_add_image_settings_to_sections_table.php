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
        Schema::table('sections', function (Blueprint $table) {
            $table->string('image')->nullable()->after('description');
            $table->string('image_alignment')->default('center')->after('image');
            $table->string('image_wrap_mode')->default('fixed')->after('image_alignment'); // 'fixed' or 'fit'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn(['image', 'image_alignment', 'image_wrap_mode']);
        });
    }
};
