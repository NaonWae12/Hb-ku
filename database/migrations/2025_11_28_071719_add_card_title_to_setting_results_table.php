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
        Schema::table('setting_results', function (Blueprint $table) {
            if (!Schema::hasColumn('setting_results', 'card_title')) {
                $table->string('card_title')->nullable()->after('rule_group_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('setting_results', function (Blueprint $table) {
            if (Schema::hasColumn('setting_results', 'card_title')) {
                $table->dropColumn('card_title');
            }
        });
    }
};
