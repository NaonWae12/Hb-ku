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
        // Get all unique rule_group_id from answer_templates
        $templateGroups = DB::table('answer_templates')
            ->select('form_id', 'rule_group_id')
            ->whereNotNull('rule_group_id')
            ->distinct()
            ->get();

        // Get all unique rule_group_id from result_rules
        $ruleGroups = DB::table('result_rules')
            ->select('form_id', 'rule_group_id')
            ->whereNotNull('rule_group_id')
            ->distinct()
            ->get();

        // Combine and get unique rule_group_ids with their form_ids
        $allGroups = collect($templateGroups)->merge($ruleGroups)
            ->groupBy('rule_group_id')
            ->map(function ($items) {
                // Get form_id from first item (they should all have same form_id for same rule_group_id)
                return [
                    'rule_group_id' => $items->first()->rule_group_id,
                    'form_id' => $items->first()->form_id,
                ];
            })
            ->values();

        // Insert into rule_groups table, skip if already exists
        foreach ($allGroups as $group) {
            $exists = DB::table('rule_groups')
                ->where('rule_group_id', $group['rule_group_id'])
                ->where('form_id', $group['form_id'])
                ->exists();

            if (!$exists) {
                DB::table('rule_groups')->insert([
                    'form_id' => $group['form_id'],
                    'rule_group_id' => $group['rule_group_id'],
                    'title' => null, // Data lama tidak punya title
                    'created_at' => now(),
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
        // Optionally, you can delete the backfilled data
        // But we'll keep it safe by not deleting anything
        // If you want to rollback, you can manually delete or create a separate migration
    }
};
