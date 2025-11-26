<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all result_settings with title and their associated result_rules
        $resultSettingsWithTitle = DB::table('result_settings')
            ->join('result_rules', 'result_settings.result_rule_id', '=', 'result_rules.id')
            ->whereNotNull('result_settings.title')
            ->where('result_settings.title', '!=', '')
            ->select(
                'result_settings.title',
                'result_rules.rule_group_id',
                'result_rules.form_id'
            )
            ->distinct()
            ->get();

        // Update rule_groups with title from result_settings
        foreach ($resultSettingsWithTitle as $setting) {
            if ($setting->rule_group_id && $setting->form_id) {
                DB::table('rule_groups')
                    ->where('rule_group_id', $setting->rule_group_id)
                    ->where('form_id', $setting->form_id)
                    ->update([
                        'title' => $setting->title,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally, you can clear titles if needed
        // But we'll keep it safe by not doing anything
    }
};
